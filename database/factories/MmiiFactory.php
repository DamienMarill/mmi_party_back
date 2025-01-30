<?php

namespace Database\Factories;

use App\Enums\StableDiffusionPreset;
use App\Services\MMIIService;
use App\Services\PlaceholderService;
use App\Services\StableDiffusionService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mmii>
 */
class MmiiFactory extends Factory
{
    protected $model = \App\Models\Mmii::class;

    protected MMIIService $mmiiService;
    protected array $availableParts;
    protected PlaceholderService $placeholderService;

    public function __construct()
    {
        parent::__construct();
        $this->mmiiService = new MMIIService();
        $this->availableParts = $this->mmiiService->getAvailablePartsWithAssets();
        $this->placeholderService = new PlaceholderService();
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


    public function definition(): array
    {
        Log::debug('Available parts:', $this->availableParts);
        Log::debug('Available backgrounds:', $this->mmiiService->getBackgroundsFiles());

        $shape = [
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
            ],
            'pull' => [
                'img' => $this->getRandomFile('pull'),
                'color' => $this->getRandomColor('pull')
            ],
            'sourcils' => [
                'img' => $this->getRandomFile('sourcils'),
                'color' => $this->getRandomColor('sourcils')
            ]
        ];

        Log::debug('Generated shape:', $shape);

        return [
            'shape' => $shape,
            'background' => $this->getRandomBackground(),
        ];
    }

    // Méthode pour skip la génération d'image
    public function withoutImage()
    {
        return $this->state(function (array $attributes) {
            return ['background' => null];
        });
    }
}
