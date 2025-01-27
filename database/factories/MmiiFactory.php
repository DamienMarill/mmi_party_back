<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mmii>
 */
class MmiiFactory extends Factory
{
    protected $model = \App\Models\Mmii::class;

    protected MMIIService $mmiiService;
    protected array $availableParts;

    public function __construct()
    {
        parent::__construct();
        $this->mmiiService = new MMIIService();
        $this->availableParts = $this->mmiiService->getAvailablePartsWithAssets();
    }

    private function getRandomFile(string $part): ?string
    {
        $files = $this->availableParts[$part]['files'] ?? [];
        return !empty($files) ? $this->faker->randomElement($files) : null;
    }

    private function getRandomColor(string $part): ?string
    {
        $colors = $this->availableParts[$part]['availableColors'] ?? [];
        return !empty($colors) ? $this->faker->randomElement($colors) : null;
    }

    public function definition(): array
    {
        $shape = [];

        // Parties obligatoires
        $shape['bouche'] = [
            'img' => $this->getRandomFile('bouche')
        ];

        $shape['nez'] = [
            'img' => $this->getRandomFile('nez')
        ];

        $shape['tete'] = [
            'img' => $this->getRandomFile('tete'),
            'color' => $this->getRandomColor('tete')
        ];

        $shape['yeux'] = [
            'img' => $this->getRandomFile('yeux'),
            'color' => $this->getRandomColor('yeux')
        ];

        // Parties optionnelles (avec 70% de chance d'apparaître)
        $optionalParts = ['cheveux', 'maquillage', 'particularites', 'pilosite'];

        foreach ($optionalParts as $part) {
            if ($this->faker->boolean(70)) {
                $shape[$part] = [
                    'img' => $this->getRandomFile($part)
                ];

                // Ajouter la couleur si nécessaire
                if ($this->availableParts[$part]['requiresColor']) {
                    $shape[$part]['color'] = $this->getRandomColor($part);
                }
            }
        }

        return [
            'shape' => $shape
        ];
    }

    // États spéciaux
    public function withAllParts()
    {
        return $this->state(function (array $attributes) {
            $shape = $attributes['shape'];

            foreach ($this->availableParts as $part => $data) {
                if (!isset($shape[$part])) {
                    $shape[$part] = [
                        'img' => $this->getRandomFile($part)
                    ];

                    if ($data['requiresColor']) {
                        $shape[$part]['color'] = $this->getRandomColor($part);
                    }
                }
            }

            return ['shape' => $shape];
        });
    }

    public function minimal()
    {
        return $this->state(function (array $attributes) {
            return [
                'shape' => [
                    'bouche' => [
                        'img' => $this->getRandomFile('bouche')
                    ],
                    'nez' => [
                        'img' => $this->getRandomFile('nez')
                    ],
                    'tete' => [
                        'img' => $this->getRandomFile('tete'),
                        'color' => $this->getRandomColor('tete')
                    ],
                    'yeux' => [
                        'img' => $this->getRandomFile('yeux'),
                        'color' => $this->getRandomColor('yeux')
                    ]
                ]
            ];
        });
    }
}
