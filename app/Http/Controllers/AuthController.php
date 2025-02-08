<?php

namespace App\Http\Controllers;

use App\Enums\UserGroups;
use App\Models\CardTemplate;
use App\Models\Mmii;
use App\Models\RefreshToken;
use App\Models\User;
use App\Notifications\VerificationMail;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'um_email' => 'required|string|email|max:255|unique:users|regex:/^[a-zA-Z0-9._%+-]+@(etu\.)?umontpellier\.fr$/',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $groupe = [
            'etu.umontpellier.fr' => UserGroups::STUDENT,
            'umontpellier.fr' => UserGroups::STAFF,
        ][
            explode('@', $request->um_email)[1]
        ];

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'um_email' => $request->um_email,
            'password' => Hash::make($request->password),
            'groupe' => $groupe,
        ]);

        // Envoyer l'email de vérification
        $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put('verification_code_' . $user->id, $verificationCode, now()->addHours(1));

        // Envoi du mail de vérification
        $user->notify(new VerificationMail($verificationCode));

        DB::commit();

        return $user;
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Email ou mot de passe incorrect!'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function refresh(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string'
        ]);

        $refreshToken = RefreshToken::where('token', $request->refresh_token)
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$refreshToken) {
            return response()->json(['error' => 'Invalid refresh token'], 401);
        }

        // Révoquer le refresh token actuel
        $refreshToken->update(['revoked' => true]);

        // Générer un nouveau JWT token
        $user = User::find($refreshToken->user_id);
        $token = auth()->fromUser($user);
        auth()->login($user);

        Log::info('token '.$token);

        return $this->respondWithToken($token);
    }


    public function logout()
    {
        // Révoquer tous les refresh tokens
        RefreshToken::where('user_id', auth()->id())
            ->where('revoked', false)
            ->update(['revoked' => true]);

        auth()->logout();
        return response()->json(['message' => 'À bientôt Senpai! (｀･ω･´)ゞ']);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Email de réinitialisation envoyé!'])
            : response()->json(['error' => 'Une erreur est survenue'], 400);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ], [
            'password.required' => 'Le mot de passe est requis',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères',
            'password.confirmed' => 'Les mots de passe ne correspondent pas',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Mot de passe réinitialisé avec succès']);
        }

        return response()->json(['error' => 'Token invalide'], 400);
    }

    protected function respondWithToken($token): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $refreshToken = $this->createRefreshToken($user);

        return response()->json([
            'access_token' => $token,
            'refresh_token' => $refreshToken->token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => $user
        ]);
    }

    protected function createRefreshToken($user)
    {
        Log::info($user);
        // Révoquer les anciens refresh tokens
        RefreshToken::where('user_id', $user->id)
            ->where('revoked', false)
            ->update(['revoked' => true]);

        return RefreshToken::create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(30),
        ]);
    }

    public function verifyCode(Request $request)
    {
        $validated = $request->validate([
            'registrationId' => 'required|exists:users,id',
            'code' => 'required|string|size:6'
        ]);

        $cachedCode = Cache::get('verification_code_' . $validated['registrationId']);

        if (!$cachedCode || $cachedCode !== $validated['code']) {
            return response()->json([
                'message' => 'Code de vérification invalide.'
            ], 422);
        }

        $user = User::findOrFail($validated['registrationId']);
        $user->email_verified_at = now();
        $user->save();

        Cache::forget('verification_code_' . $validated['registrationId']);

        return response()->json([
            'message' => 'Email vérifié avec succès.'
        ]);
    }

    public function finalizeRegistration(Request $request, $registrationId)
    {
        $user = User::findOrFail($registrationId);

        if (!$user->email_verified_at) {
            return response()->json([
                'message' => 'L\'email universitaire doit être vérifié.'
            ], 422);
        }

        $validated = $request->validate([
            'studentType' => 'required_if:um_email,*@etu.umontpellier.fr',
            'mmiiData' => 'sometimes|array',
            'background' => 'required|string',
            'skills' => 'sometimes|array'
        ]);

        if ($user->groupe === UserGroups::STUDENT && in_array($validated['studentType'], ['MMI1', 'MMI2', 'MMI3'])) {
            $user->groupe = strtolower($validated['studentType']);
        }

        if ($validated['mmiiData']) {
            $mmii = new Mmii();
            $mmii->background = $validated['background'];
            $mmii->shape = $validated['mmiiData'];
            $mmii->save();

            $user->mmii_id = $mmii->id;
        }

        $user->save();

        if ($validated['skills']) {
            //récupérer le dernier caractère du groupe du user
            $userGroupe = substr($user->groupe->value, -1);

            $template = CardTemplate::where('base_user', null)
                ->where('type', 'STUDENT')
                ->where('level', $userGroupe)
                ->first();

            $template->base_user = $user->id;
            $template->name = $user->name;
            $template->stats = $validated['skills'];
            $template->mmii_id = $user->mmii_id;
            $template->save();
        }

        return response()->json([
            'message' => 'Inscription finalisée avec succès.'
        ]);
    }
}
