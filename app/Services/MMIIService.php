<?php

namespace App\Services;

use App\Enums\MMIIBodyPart;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class MMIIService
{
    private string $basePath;
    private array $availableColors;

    public function __construct()
    {
        $this->basePath = Config::get('mmii.asset_path');
        $this->availableColors = Config::get('mmii.colors');
    }

    public function getAvailablePartsWithAssets(): array
    {
        $parts = [];

        foreach(MMIIBodyPart::cases() as $part) {
            $files = $this->getFilesForPart($part->value);
            $colors = $part->requiresColor() ?
                ($this->availableColors[$part->value] ?? []) :
                null;

            $parts[$part->value] = [
                'files' => $files,
                'requiresColor' => $part->requiresColor(),
                'availableColors' => $colors,
                'mixBlendMode' => $part->mixBlenMode()
            ];
        }

        return $parts;
    }

    private function getFilesForPart(string $part): array
    {
        $path = "{$this->basePath}/{$part}";
        return collect(Storage::disk('public')->files($path))
            ->map(fn($file) => basename($file))
            ->filter(fn($file) => str_ends_with($file, '.png'))
            ->values()
            ->toArray();
    }

    public function validateShapeJson(array $shape): bool
    {
        try {
            foreach(MMIIBodyPart::cases() as $part) {
                $partName = $part->value;

                // Vérifie si la partie est optionnelle
                if (!in_array($part, [MMIIBodyPart::CHEVEUX, MMIIBodyPart::MAQUILLAGE,
                        MMIIBodyPart::PARTICULARITES, MMIIBodyPart::PILOSITE])
                    && !isset($shape[$partName])) {
                    throw new InvalidArgumentException("La partie {$partName} est requise");
                }

                if (isset($shape[$partName])) {
                    // Vérifie la présence de l'image
                    if (!isset($shape[$partName]['img'])) {
                        throw new InvalidArgumentException("L'image est requise pour {$partName}");
                    }

                    // Vérifie que le fichier existe
                    if (!in_array($shape[$partName]['img'], $this->getFilesForPart($partName))) {
                        throw new InvalidArgumentException("L'image {$shape[$partName]['img']} n'existe pas pour {$partName}");
                    }

                    // Vérifie la couleur si nécessaire
                    if ($part->requiresColor()) {
                        if (!isset($shape[$partName]['color'])) {
                            throw new InvalidArgumentException("La couleur est requise pour {$partName}");
                        }

                        if (!in_array($shape[$partName]['color'], $this->availableColors[$partName] ?? [])) {
                            throw new InvalidArgumentException("La couleur {$shape[$partName]['color']} n'est pas valide pour {$partName}");
                        }
                    }
                }
            }

            return true;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    public function getValidationErrors(array $shape): array
    {
        $errors = [];

        try {
            $this->validateShapeJson($shape);
        } catch (InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        }

        return $errors;
    }
}
