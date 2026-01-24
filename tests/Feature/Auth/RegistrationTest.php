<?php

namespace Tests\Feature\Auth;

use App\Enums\UserGroups;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    // ========== Tests de validation ==========

    public function test_registration_requires_name(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'test@example.com',
            'um_email' => 'test@etu.umontpellier.fr',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    public function test_registration_requires_valid_email(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'um_email' => 'test@etu.umontpellier.fr',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_requires_um_email(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['um_email']);
    }

    public function test_registration_rejects_non_um_email(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'um_email' => 'test@gmail.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['um_email']);
    }

    public function test_registration_requires_password_min_8_chars(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'um_email' => 'test@etu.umontpellier.fr',
            'password' => 'short',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    // ========== Tests d'inscription reussie ==========

    public function test_student_can_register_with_etu_email(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Etudiant Test',
            'email' => 'etudiant@example.com',
            'um_email' => 'etudiant.test@etu.umontpellier.fr',
            'password' => 'password123',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'etudiant@example.com',
            'um_email' => 'etudiant.test@etu.umontpellier.fr',
            'groupe' => UserGroups::STUDENT->value,
        ]);
    }

    public function test_staff_can_register_with_um_email(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Prof Test',
            'email' => 'prof@example.com',
            'um_email' => 'prof.test@umontpellier.fr',
            'password' => 'password123',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'prof@example.com',
            'groupe' => UserGroups::STAFF->value,
        ]);
    }

    public function test_verification_code_is_cached_after_registration(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'um_email' => 'test@etu.umontpellier.fr',
            'password' => 'password123',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'test@example.com')->first();
        $cachedCode = Cache::get('verification_code_' . $user->id);

        $this->assertNotNull($cachedCode);
        $this->assertEquals(6, strlen($cachedCode));
    }

    // ========== Tests d'unicite ==========

    public function test_cannot_register_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'um_email' => 'test@etu.umontpellier.fr',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_cannot_register_with_duplicate_um_email(): void
    {
        User::factory()->create(['um_email' => 'existing@etu.umontpellier.fr']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'new@example.com',
            'um_email' => 'existing@etu.umontpellier.fr',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['um_email']);
    }
}
