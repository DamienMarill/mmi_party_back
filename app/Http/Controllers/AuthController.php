<?php

namespace App\Http\Controllers;

use App\Enums\UserGroups;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'password' => 'required|string|min:8|uppercase|lowercase|number|special|uncompromised',
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
        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Inscription réussie! Vérifie ton email Senpai!'], 201);
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
            'password' => 'required|confirmed|min:8',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Mot de passe réinitialisé!'])
            : response()->json(['error' => 'Une erreur est survenue'], 400);
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
}
