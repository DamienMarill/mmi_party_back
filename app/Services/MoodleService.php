<?php

namespace App\Services;

use App\Enums\UserGroups;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MoodleService
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('moodle.url'), '/');
        $this->clientId = config('moodle.client_id');
        $this->clientSecret = config('moodle.client_secret');
        $this->redirectUri = config('moodle.redirect_uri');
    }

    /**
     * Generate the Moodle OAuth authorization URL.
     */
    public function getAuthorizationUrl(string $state): string
    {
        $params = http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'state' => $state,
        ]);

        return "{$this->baseUrl}/local/oauth/login.php?{$params}";
    }

    /**
     * Exchange authorization code for access token.
     */
    public function exchangeCodeForToken(string $code): array
    {
        $response = Http::asForm()->post("{$this->baseUrl}/local/oauth/token.php", [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'scope' => 'user_info',
        ]);

        if (!$response->successful()) {
            Log::error('Moodle token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to exchange Moodle authorization code');
        }

        return $response->json();
    }

    /**
     * Get user info from Moodle using access token.
     * Returns user data including cohorts (if plugin is modified).
     */
    public function getUserInfo(string $accessToken): array
    {
        $response = Http::asForm()->post("{$this->baseUrl}/local/oauth/user_info.php", [
            'access_token' => $accessToken,
        ]);

        if (!$response->successful()) {
            Log::error('Moodle user info request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to get Moodle user info');
        }

        return $response->json();
    }

    /**
     * Check if user has required cohorts to access the app.
     */
    public function hasRequiredCohorts(array $cohorts): bool
    {
        $requiredCohorts = config('moodle.required_cohorts', []);

        foreach ($requiredCohorts as $required) {
            if (in_array($required, $cohorts)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine user group based on cohort membership.
     * Returns the most specific group (MMI3 > MMI2 > MMI1 > STAFF > STUDENT).
     */
    public function determineUserGroup(array $cohorts): UserGroups
    {
        $mapping = config('moodle.cohort_mapping', []);

        foreach ($mapping as $group => $cohortNames) {
            foreach ($cohortNames as $cohortName) {
                if (in_array($cohortName, $cohorts)) {
                    return UserGroups::from($group);
                }
            }
        }

        // Default: if user has MMI cohort but no specific year, mark as student
        if (in_array('MMI', $cohorts)) {
            return UserGroups::STUDENT;
        }

        // Staff fallback for teachers without specific cohort
        return UserGroups::STAFF;
    }

    /**
     * Generate a random state token for CSRF protection.
     */
    public function generateState(): string
    {
        return Str::random(40);
    }
}
