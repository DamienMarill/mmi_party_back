<?php

namespace Database\Factories;

use App\Enums\CardRarity;
use App\Models\CardTemplate;
use App\Models\CardVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CardVersion>
 */
class CardVersionFactory extends Factory
{
    protected $model = CardVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'card_template_id' => CardTemplate::factory(),
            'rarity' => $this->faker->randomElement(CardRarity::cases()),
            'image' => null, // Placeholder pour le dev
        ];
    }

    // State pour une rareté spécifique
    public function withRarity(CardRarity $rarity): static
    {
        return $this->state(fn () => ['rarity' => $rarity->value]);
    }

// States pour chaque rareté
    public function common(): static
    {
        return $this->withRarity(CardRarity::COMMON);
    }

    public function uncommon(): static
    {
        return $this->withRarity(CardRarity::UNCOMMON);
    }

    public function rare(): static
    {
        return $this->withRarity(CardRarity::RARE);
    }

    public function epic(): static
    {
        return $this->withRarity(CardRarity::EPIC);
    }

// State pour un template spécifique
    public function forTemplate($template): static
    {
        $templateId = is_string($template) ? $template : $template->id;
        return $this->state(fn () => ['card_template_id' => $templateId]);
    }

// State pour version sans image
    public function withoutImage(): static
    {
        return $this->state(fn () => ['image' => null]);
    }

// State pour une image spécifique
    public function withImage(string $path): static
    {
        return $this->state(fn () => ['image' => $path]);
    }

// Helper pour créer plusieurs versions pour un même template
    public function createVariants(string $templateId, int $count = 4): array
    {
        $rarities = [
            CardRarity::COMMON,
            CardRarity::UNCOMMON,
            CardRarity::RARE,
            CardRarity::EPIC,
        ];

        $versions = [];
        for ($i = 0; $i < min($count, count($rarities)); $i++) {
            $versions[] = $this->forTemplate($templateId)
                ->withRarity($rarities[$i])
                ->create();
        }

        return $versions;
    }
}
