<?php

namespace App\Http\Controllers;

use App\Enums\UserGroups;
use App\Models\RefreshToken;
use App\Models\User;
use App\Services\MoodleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MoodleAuthController extends Controller
{
    public function __construct(
        private MoodleService $moodleService
    ) {
    }

    /**
     * Redirect user to Moodle OAuth login page.
     */
    public function redirect(Request $request): JsonResponse|RedirectResponse
    {
        $state = $this->moodleService->generateState();
        $from = $request->query('from', 'app');

        // Store state + origin in cache for CSRF validation (5 minutes)
        Cache::put("moodle_oauth_state_{$state}", ['from' => $from], now()->addMinutes(5));

        $authUrl = $this->moodleService->getAuthorizationUrl($state);

        // If API request, return URL; otherwise redirect
        if ($request->wantsJson() || $request->has('json')) {
            return response()->json([
                'url' => $authUrl,
                'state' => $state,
            ]);
        }

        return redirect()->away($authUrl);
    }

    /**
     * Handle callback from Moodle OAuth.
     */
    public function callback(Request $request): RedirectResponse|JsonResponse
    {
        $code = $request->query('code');
        $state = $request->query('state');
        $error = $request->query('error');

        // Handle OAuth errors
        if ($error) {
            Log::warning('Moodle OAuth error', ['error' => $error]);
            return $this->redirectToFrontend('error', ['message' => 'Authentification Moodle refusée']);
        }

        // Validate required parameters
        if (!$code || !$state) {
            return $this->redirectToFrontend('error', ['message' => 'Paramètres OAuth manquants']);
        }

        // Validate state (CSRF protection)
        $stateData = Cache::pull("moodle_oauth_state_{$state}");
        if (!$stateData) {
            return $this->redirectToFrontend('error', ['message' => 'Session expirée, veuillez réessayer']);
        }

        $from = is_array($stateData) ? ($stateData['from'] ?? 'app') : 'app';

        try {
            // Exchange code for token
            $tokenData = $this->moodleService->exchangeCodeForToken($code);
            $accessToken = $tokenData['access_token'] ?? null;

            if (!$accessToken) {
                throw new \Exception('No access token received');
            }

            // Get user info from Moodle
            $moodleUser = $this->moodleService->getUserInfo($accessToken);

            Log::info('Moodle user info received', ['user' => $moodleUser]);

            // Extract cohorts
            $cohorts = $moodleUser['cohorts'] ?? [];

            // Validate access (must be in MMI or Enseignants MMI)
            if (!$this->moodleService->hasRequiredCohorts($cohorts)) {
                if ($from === 'admin') {
                    return redirect()->to('/admin/login?error=' . urlencode('Accès réservé aux étudiants et enseignants MMI'));
                }
                return $this->redirectToFrontend('error', [
                    'message' => 'Accès réservé aux étudiants et enseignants MMI'
                ]);
            }

            // Determine user group from cohorts
            $group = $this->moodleService->determineUserGroup($cohorts);

            // Find or create user
            $user = $this->findOrCreateUser($moodleUser, $group);

            // Admin panel flow: store user in cache, redirect to web route for session login
            if ($from === 'admin') {
                if (!$user->is_admin) {
                    return redirect()->to('/admin/login?error=' . urlencode('Vous n\'avez pas les droits administrateur.'));
                }

                $loginToken = Str::random(64);
                Cache::put("admin_login_token_{$loginToken}", $user->id, now()->addMinutes(1));

                return redirect()->to('/admin/auth/moodle/finalize?token=' . $loginToken);
            }

            // API flow: JWT tokens + redirect to frontend
            $token = \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::fromUser($user);
            $refreshToken = $this->createRefreshToken($user);

            // Check if user needs to complete registration (no MMII avatar)
            $needsFinalization = !$user->mmii_id;

            return $this->redirectToFrontend('success', [
                'access_token' => $token,
                'refresh_token' => $refreshToken->token,
                'needs_finalization' => $needsFinalization,
                'user_id' => $user->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Moodle OAuth callback error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($from === 'admin') {
                return redirect()->to('/admin/login?error=' . urlencode('Erreur lors de la connexion Moodle'));
            }

            return $this->redirectToFrontend('error', [
                'message' => 'Erreur lors de la connexion Moodle'
            ]);
        }
    }

    /**
     * Finalize admin login via session (called from web route with session middleware).
     */
    public function adminFinalize(Request $request): RedirectResponse
    {
        $token = $request->query('token');

        if (!$token) {
            return redirect()->to('/admin/login?error=' . urlencode('Token manquant.'));
        }

        $userId = Cache::pull("admin_login_token_{$token}");

        if (!$userId) {
            return redirect()->to('/admin/login?error=' . urlencode('Session expirée, veuillez réessayer.'));
        }

        $user = User::find($userId);

        if (!$user || !$user->is_admin) {
            return redirect()->to('/admin/login?error=' . urlencode('Accès non autorisé.'));
        }

        auth()->guard('web')->login($user);

        return redirect()->to('/admin');
    }

    /**
     * Find existing user by email or create new one.
     */
    private function findOrCreateUser(array $moodleUser, UserGroups $group): User
    {
        $moodleId = $moodleUser['id'];
        $email = $moodleUser['email'];
        $username = $moodleUser['username'];
        $firstname = $moodleUser['firstname'] ?? '';
        $lastname = $moodleUser['lastname'] ?? '';
        $name = trim("{$firstname} {$lastname}") ?: $username;

        // First, try to find by moodle_id
        $user = User::where('moodle_id', $moodleId)->first();

        if ($user) {
            // Update group if changed
            if ($user->groupe !== $group) {
                $user->groupe = $group;
                $user->save();
            }
            return $user;
        }

        // Try to find by um_email (match existing accounts)
        $user = User::where('um_email', $email)->first();

        if ($user) {
            // Link Moodle account to existing user
            $user->moodle_id = $moodleId;
            $user->moodle_username = $username;
            $user->groupe = $group;
            $user->email_verified_at = $user->email_verified_at ?? now();
            $user->save();
            return $user;
        }

        // Create new user
        return User::create([
            'name' => $name,
            'email' => $email, // Use Moodle email as primary
            'um_email' => $email,
            'moodle_id' => $moodleId,
            'moodle_username' => $username,
            'groupe' => $group,
            'email_verified_at' => now(), // Auto-verify since Moodle confirms identity
        ]);
    }

    /**
     * Create refresh token for user.
     */
    private function createRefreshToken(User $user): RefreshToken
    {
        // Revoke old refresh tokens
        RefreshToken::where('user_id', $user->id)
            ->where('revoked', false)
            ->update(['revoked' => true]);

        return RefreshToken::create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(30),
        ]);
    }

    /**
     * Redirect to frontend with parameters.
     */
    private function redirectToFrontend(string $type, array $params): RedirectResponse
    {
        $baseUrl = match ($type) {
            'success' => config('moodle.frontend_success_url'),
            'register' => config('moodle.frontend_register_url'),
            default => config('moodle.frontend_error_url'),
        };

        $query = http_build_query($params);

        return redirect()->away("{$baseUrl}?{$query}");
    }
}
