<?php

namespace Database\Factories;

use App\Enums\StableDiffusionPreset;
use App\Services\MMIIService;
use App\Services\PlaceholderService;
use App\Services\StableDiffusionService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Log;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mmii>
 */
class MmiiFactory extends Factory
{
    protected $model = \App\Models\Mmii::class;

    protected MMIIService $mmiiService;
    protected array $availableParts;
    protected PlaceholderService $placeholderService;
    protected StableDiffusionService $stableDiffusion;

    public function __construct()
    {
        parent::__construct();
        $this->mmiiService = new MMIIService();
        $this->availableParts = $this->mmiiService->getAvailablePartsWithAssets();
        $this->placeholderService = new PlaceholderService();
        $this->stableDiffusion = new StableDiffusionService();
    }

    public function getRandomFile(string $part): ?string
    {
        $files = $this->availableParts[$part]['files'] ?? [];
        return !empty($files) ? $this->faker->randomElement($files) : null;
    }

    public function getRandomBackground(): ?string
    {
        $files = $this->mmiiService->getBackgroundsFiles();
        return !empty($files) ? $this->faker->randomElement($files) : null;
    }

    public function getRandomColor(string $part): ?string
    {
        $colors = $this->availableParts[$part]['availableColors'] ?? [];
        return !empty($colors) ? $this->faker->randomElement($colors) : null;
    }

    private function getRandomPreset(): StableDiffusionPreset
    {
        $cases = StableDiffusionPreset::cases();
        return $cases[array_rand($cases)];
    }


    public function definition(): array
    {
        $shape = $this->minimal();
        try {
            // Récupérer une image placeholder
            $baseImage = $this->placeholderService->getRandomImage();

            // Générer l'image avec Stable Diffusion
            $result = $this->stableDiffusion->img2img(
                $baseImage,
                $this->getRandomPreset()
            );

            // L'API renvoie les images en base64
            $generatedImage = $result['images'][0] ?? null;

            return [
                'shapes' => $shape,
                'background' => $generatedImage
            ];
        } catch (\Exception $e) {
            Log::error('Failed to generate image for MMII', [
                'error' => $e->getMessage()
            ]);

            // Retourner juste le shape si la génération échoue
            return [
                'shapes' => $shape,
                'background' => null
            ];
        }
    }

    public function minimal()
    {
        return $this->state(function (array $attributes) {
            return [
                'shape' => [
                    'bouche' => [
                        'img' => $this->getRandomFile('bouche'),
                        'color' => $this->getRandomColor('bouche')
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
                    ],
                    'cheveux' => [
                        'img' => $this->getRandomFile('cheveux'),
                        'color' => $this->getRandomColor('cheveux')
                    ],
                    'maquillage' => [
                        'img' => $this->getRandomFile('maquillage')
                    ],
                    'particularites' => [
                        'img' => $this->getRandomFile('particularites')
                    ],
                    'pilosite' => [
                        'img' => $this->getRandomFile('pilosite'),
                        'color' => $this->getRandomColor('pilosite')
                    ]
                ]
            ];
        });
    }

    // Méthode pour forcer l'utilisation d'un preset spécifique
    public function withPreset(StableDiffusionPreset $preset)
    {
        return $this->state(function (array $attributes) use ($preset) {
            try {
                $baseImage = $this->placeholderService->getRandomImage();
                $result = $this->stableDiffusion->img2img(
                    $baseImage,
                    $preset
                );

                return [
                    'background' => $result['images'][0] ?? null
                ];
            } catch (\Exception $e) {
                Log::error('Failed to generate image with preset', [
                    'preset' => $preset->value,
                    'error' => $e->getMessage()
                ]);

                return ['image' => null];
            }
        });
    }

    // Méthode pour skip la génération d'image
    public function withoutImage()
    {
        return $this->state(function (array $attributes) {
            return ['background' => null];
        });
    }
}
