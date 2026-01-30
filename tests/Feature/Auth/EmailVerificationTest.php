<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $verificationCode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->unverified()->create();
        $this->verificationCode = '123456';

        Cache::put('verification_code_' . $this->user->id, $this->verificationCode, now()->addHours(1));
    }

    // ========== Tests de validation ==========

    public function test_verify_code_requires_registration_id(): void
    {
        $response = $this->postJson('/api/me/verify_code', [
            'code' => '123456',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['registrationId']);
    }

    public function test_verify_code_requires_code(): void
    {
        $response = $this->postJson('/api/me/verify_code', [
            'registrationId' => $this->user->id,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['code']);
    }

    public function test_verify_code_requires_6_digit_code(): void
    {
        $response = $this->postJson('/api/me/verify_code', [
            'registrationId' => $this->user->id,
            'code' => '123', // Trop court
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['code']);
    }

    public function test_verify_code_requires_existing_user(): void
    {
        $response = $this->postJson('/api/me/verify_code', [
            'registrationId' => 'nonexistent-uuid',
            'code' => '123456',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['registrationId']);
    }

    // ========== Tests de verification reussie ==========

    public function test_can_verify_email_with_correct_code(): void
    {
        $response = $this->postJson('/api/me/verify_code', [
            'registrationId' => $this->user->id,
            'code' => $this->verificationCode,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Email vérifié avec succès.']);

        $this->user->refresh();
        $this->assertNotNull($this->user->email_verified_at);
    }

    public function test_verification_code_is_cleared_after_success(): void
    {
        $this->postJson('/api/me/verify_code', [
            'registrationId' => $this->user->id,
            'code' => $this->verificationCode,
        ]);

        $this->assertNull(Cache::get('verification_code_' . $this->user->id));
    }

    // ========== Tests de verification echouee ==========

    public function test_cannot_verify_with_wrong_code(): void
    {
        $response = $this->postJson('/api/me/verify_code', [
            'registrationId' => $this->user->id,
            'code' => '000000',
        ]);

        $response->assertStatus(422)
                 ->assertJson(['message' => 'Code de vérification invalide.']);

        $this->user->refresh();
        $this->assertNull($this->user->email_verified_at);
    }

    public function test_cannot_verify_with_expired_code(): void
    {
        Cache::forget('verification_code_' . $this->user->id);

        $response = $this->postJson('/api/me/verify_code', [
            'registrationId' => $this->user->id,
            'code' => $this->verificationCode,
        ]);

        $response->assertStatus(422)
                 ->assertJson(['message' => 'Code de vérification invalide.']);
    }

    // ========== Tests de throttling ==========

    public function test_verify_code_is_throttled(): void
    {
        // Envoyer 7 requetes (limite = 6)
        for ($i = 0; $i < 7; $i++) {
            $response = $this->postJson('/api/me/verify_code', [
                'registrationId' => $this->user->id,
                'code' => '000000',
            ]);
        }

        $response->assertStatus(429); // Too Many Requests
    }
}
