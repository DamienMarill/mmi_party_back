<?php

namespace Database\Factories;

use App\Enums\UserGroups;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();
        $groupe = $this->faker->randomElement(UserGroups::cases());

        return [
            'name' => "$firstName $lastName",
            'email' => $this->faker->unique()->safeEmail(),
            'um_email' => null,
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'github_id' => null,
            'groupe' => $groupe,
            'remember_token' => Str::random(10),
        ];
    }

// User non vérifié
    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }

// User avec GitHub
    public function withGithub(): static
    {
        return $this->state(fn () => [
            'github_id' => (string) $this->faker->unique()->numberBetween(10000, 99999),
            'password' => null,
        ]);
    }

// User avec email UM
    public function withUmEmail(): static
    {
        return $this->state(function (array $attributes) {
            $firstName = strtolower(explode(' ', $attributes['name'])[0]);
            $lastName = strtolower(explode(' ', $attributes['name'])[1]);

            return [
                'um_email' => "{$firstName}.{$lastName}@etu.umontpellier.fr",
            ];
        });
    }

// User étudiant
    public function student(): static
    {
        return $this->state(fn () => [
            'groupe' => UserGroups::STUDENT,
        ])->withUmEmail();
    }

// User prof
    public function staff(): static
    {
        return $this->state(function () {
            $firstName = $this->faker->firstName();
            $lastName = $this->faker->lastName();

            return [
                'name' => "Prof. $firstName $lastName",
                'groupe' => UserGroups::STAFF,
                'um_email' => "{$firstName}.{$lastName}@umontpellier.fr",
            ];
        });
    }

// User MMI1
    public function mmi1(): static
    {
        return $this->student()->state(fn () => [
            'groupe' => UserGroups::MMI1,
        ]);
    }

// User MMI2
    public function mmi2(): static
    {
        return $this->student()->state(fn () => [
            'groupe' => UserGroups::MMI2,
        ]);
    }

// User MMI3
    public function mmi3(): static
    {
        return $this->student()->state(fn () => [
            'groupe' => UserGroups::MMI3,
        ]);
    }

}
