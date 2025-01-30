<?php

namespace Database\Factories;

use App\Enums\CardTypes;
use App\Models\CardTemplate;
use App\Services\ShapeValidator;
use App\Services\StatsValidator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CardTemplate>
 */
class CardTemplateFactory extends Factory
{
    protected $model = CardTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(CardTypes::cases());

        return match($type) {
            CardTypes::STUDENT => $this->studentCard(),
            CardTypes::STAFF => $this->staffCard(),
            CardTypes::OBJECT => $this->objectCard(),
        };
    }

    private function studentCard(): array
    {
        $level = $this->faker->numberBetween(1, 3);

        return [
            'name' => $this->faker->name(),
            'type' => CardTypes::STUDENT,
            'level' => $level,
            'stats' => StatsValidator::generate($level),
            'shape' => ShapeValidator::generate($level),
            'mmii_id' => null, // A définir si besoin via ->state()
            'base_user' => null, // A définir si besoin via ->state()
        ];
    }

    private function staffCard(): array
    {
        return [
            'name' => $this->faker->name(),
            'type' => CardTypes::STAFF,
            'level' => null,
            'stats' => null,
            'shape' => null,
            'mmii_id' => null,
            'base_user' => null,
        ];
    }

    private function objectCard(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Laptop Pro',
                'Tablette Graphique',
                'Casque Audio',
                'BlackMagic',
                'Suite Adobe',
                'VS Code',
                'Café Double',
                'Energy Drink',
                'Clé USB 1To',
                'Second Écran',
                'Casque VR',
                'Fond Vert',
                'Mac Pro',
                'Unity',
                'Figma',
                'GitHub',
                'Miku Miku beam',
                'ChatGPT',
                'Claude.ai',
            ]),
            'type' => CardTypes::OBJECT,
            'level' => null,
            'stats' => null,
            'shape' => null,
            'mmii_id' => null,
            'base_user' => null,
        ];
    }

// States personnalisés
    public function student(): static
    {
        return $this->state(fn () => ['type' => CardTypes::STUDENT]);
    }

    public function staff(): static
    {
        return $this->state(fn () => ['type' => CardTypes::STAFF]);
    }

    public function object(): static
    {
        return $this->state(fn () => ['type' => CardTypes::OBJECT]);
    }

    public function withLevel(int $level): static
    {
        return $this->state(function () use ($level) {
            return [
                'level' => $level,
                'stats' => StatsValidator::generate($level),
                'shape' => ShapeValidator::generate($level),
            ];
        });
    }

    public function withMmii($mmii): static
    {
        $mmiiId = $mmii->id ?? $mmii;
        return $this->state(fn () => ['mmii_id' => $mmiiId]);
    }

    public function withBaseUser(string $userId): static
    {
        return $this->state(fn () => ['base_user' => $userId]);
    }
}
