<?php

namespace Tests\Feature\Auth;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class RefreshTokenTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $validRefreshToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->validRefreshToken = Str::random(64);

        RefreshToken::create([
            'user_id' => $this->user->id,
            'token' => $this->validRefreshToken,
            'expires_at' => now()->addDays(30),
            'revoked' => false,
        ]);
    }

    // ========== Tests de validation ==========

    public function test_refresh_requires_token(): void
    {
        $response = $this->postJson('/api/auth/refresh', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['refresh_token']);
    }

    // ========== Tests de refresh reussi ==========

    public function test_can_refresh_with_valid_token(): void
    {
        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $this->validRefreshToken,
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'access_token',
                     'refresh_token',
                     'token_type',
                     'expires_in',
                 ]);
    }

    public function test_refresh_returns_new_refresh_token(): void
    {
        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $this->validRefreshToken,
        ]);

        $response->assertStatus(200);

        $newToken = $response->json('refresh_token');
        $this->assertNotEquals($this->validRefreshToken, $newToken);
    }

    public function test_old_refresh_token_is_revoked_after_use(): void
    {
        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $this->validRefreshToken,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('refresh_tokens', [
            'token' => $this->validRefreshToken,
            'revoked' => true,
        ]);
    }

    // ========== Tests de tokens invalides ==========

    public function test_cannot_refresh_with_revoked_token(): void
    {
        RefreshToken::where('token', $this->validRefreshToken)
            ->update(['revoked' => true]);

        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $this->validRefreshToken,
        ]);

        $response->assertStatus(401)
                 ->assertJson(['error' => 'Invalid refresh token']);
    }

    public function test_cannot_refresh_with_expired_token(): void
    {
        RefreshToken::where('token', $this->validRefreshToken)
            ->update(['expires_at' => now()->subDay()]);

        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $this->validRefreshToken,
        ]);

        $response->assertStatus(401)
                 ->assertJson(['error' => 'Invalid refresh token']);
    }

    public function test_cannot_refresh_with_nonexistent_token(): void
    {
        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => 'nonexistent_token',
        ]);

        $response->assertStatus(401)
                 ->assertJson(['error' => 'Invalid refresh token']);
    }

    public function test_cannot_reuse_refresh_token(): void
    {
        // Premier refresh
        $this->postJson('/api/auth/refresh', [
            'refresh_token' => $this->validRefreshToken,
        ]);

        // Deuxieme tentative avec le meme token
        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $this->validRefreshToken,
        ]);

        $response->assertStatus(401);
    }
}
