<?php

namespace App\Services;

use InvalidArgumentException;

class ShapeValidator
{
    // Vérifie si la forme est valide pour un niveau donné
    public static function isValid(array $shape, int $level): bool
    {
        // Vérifie que la forme est bien un tableau 2D de booléens
        if (!self::isValidFormat($shape)) {
            return false;
        }

        // Compte le nombre de blocs
        $blockCount = self::countBlocks($shape);

        // Vérifie que le nombre de blocs correspond au niveau
        if (!self::hasValidBlockCount($blockCount, $level)) {
            return false;
        }

        // Vérifie que les blocs sont connectés
        if (!self::isConnected($shape)) {
            return false;
        }

        return true;
    }

    // Compte le nombre de blocs (true) dans la forme
    private static function countBlocks(array $shape): int
    {
        return collect($shape)->flatten()->filter(fn($cell) => $cell === true)->count();
    }

    // Vérifie que le nombre de blocs correspond au niveau
    private static function hasValidBlockCount(int $blockCount, int $level): bool
    {
        return match($level) {
            1 => $blockCount === 2,
            2 => $blockCount === 3,
            3 => $blockCount === 4,
            default => false,
        };
    }

    // Vérifie que la forme est un tableau 2D valide
    private static function isValidFormat(array $shape): bool
    {
        if (empty($shape)) return false;

        $width = count($shape[0]);

        return collect($shape)->every(function($row) use ($width) {
            return is_array($row)
                && count($row) === $width
                && collect($row)->every(fn($cell) => is_bool($cell));
        });
    }

    // Vérifie que tous les blocs sont connectés
    private static function isConnected(array $shape): bool
    {
        $visited = array_map(
            fn($row) => array_fill(0, count($row), false),
            $shape
        );

        // Trouve le premier bloc
        $start = null;
        foreach ($shape as $i => $row) {
            foreach ($row as $j => $cell) {
                if ($cell) {
                    $start = [$i, $j];
                    break 2;
                }
            }
        }

        if (!$start) return false;

        // DFS pour vérifier la connexité
        $count = self::dfs($shape, $visited, $start[0], $start[1]);

        // Vérifie que tous les blocs sont accessibles
        return $count === self::countBlocks($shape);
    }

    // DFS récursif pour parcourir les blocs connectés
    private static function dfs(array $shape, array &$visited, int $i, int $j): int
    {
        if ($i < 0 || $i >= count($shape) ||
            $j < 0 || $j >= count($shape[0]) ||
            $visited[$i][$j] ||
            !$shape[$i][$j]) {
            return 0;
        }

        $visited[$i][$j] = true;
        $count = 1;

        // Vérifie les 4 directions
        $directions = [[0,1], [1,0], [0,-1], [-1,0]];
        foreach ($directions as [$di, $dj]) {
            $count += self::dfs($shape, $visited, $i + $di, $j + $dj);
        }

        return $count;
    }

    // Génère une forme valide pour un niveau donné
    public static function generate(int $level): array
    {
        $shapes = match($level) {
            1 => self::getMmi1Shapes(),
            2 => self::getMmi2Shapes(),
            3 => self::getMmi3Shapes(),
            default => throw new InvalidArgumentException("Niveau invalide"),
        };

        return $shapes[array_rand($shapes)];
    }

    // Formes possibles pour MMI1 (2 blocs)
    private static function getMmi1Shapes(): array
    {
        return [
            // Horizontal
            [[true, true]],
            // Vertical
            [[true], [true]]
        ];
    }

    // Formes possibles pour MMI2 (3 blocs)
    private static function getMmi2Shapes(): array
    {
        return [
            // L
            [[true, false],
                [true, true]],
            [[false, true],
                [true, true]],
            [[true, true],
                [true, false]],
            [[true, true],
                [false, true]],
            // Ligne
            [[true, true, true]],
            // Vertical
            [[true],
                [true],
                [true]]
        ];
    }

    // Formes possibles pour MMI3 (4 blocs - tetrominos)
    private static function getMmi3Shapes(): array
    {
        return [
            // T
            [[false, true, false],
                [true, true, true]],
            [[true, true, true],
                [false, true, false]],
            // L
            [[true, false],
                [true, false],
                [true, true]],
            [[true, true],
                [true, false],
                [true, false]],
            [[false, true],
                [false, true],
                [true, true]],
            [[true, true],
                [false, true],
                [false, true]],
            // Carré
            [[true, true],
                [true, true]],
            // Ligne
            [[true, true, true, true]],
            [[true],
                [true],
                [true],
                [true]],
            // Z
            [[true, true, false],
                [false, true, true]],
            // S
            [[false, true, true],
                [true, true, false]]
        ];
    }
}
