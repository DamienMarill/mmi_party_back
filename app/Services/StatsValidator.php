<?php

namespace App\Services;

class StatsValidator
{
    private const CATEGORIES = [
        'dev',
        'ux_ui',
        'graphisme',
        'audiovisuel',
        'trois_d',
        'communication'
    ];

    private const POINTS_BY_LEVEL = [
        1 => 5,
        2 => 10,
        3 => 15
    ];

    public static function getEmptyStats(): array
    {
        return array_fill_keys(self::CATEGORIES, 0);
    }

    public static function isValid(array $stats, int $level): bool
    {
        // Vérifie que toutes les catégories sont présentes
        if (array_diff(self::CATEGORIES, array_keys($stats)) !== []) {
            return false;
        }

        // Vérifie que les valeurs sont des entiers positifs
        if (!collect($stats)->every(fn($value) => is_int($value) && $value >= 0)) {
            return false;
        }

        // Vérifie que le total correspond au niveau
        return array_sum($stats) === self::POINTS_BY_LEVEL[$level];
    }

    public static function generate(int $level): array
    {
        $stats = self::getEmptyStats();
        $remainingPoints = self::POINTS_BY_LEVEL[$level];
        $categories = self::CATEGORIES;

        // Assure au moins 1 point dans une catégorie aléatoire
        $primarySkill = $categories[array_rand($categories)];
        $stats[$primarySkill] = 1;
        $remainingPoints--;

        // Distribue les points restants aléatoirement
        while ($remainingPoints > 0) {
            $category = $categories[array_rand($categories)];
            $stats[$category]++;
            $remainingPoints--;
        }

        return $stats;
    }

    // Méthode utilitaire pour avoir un aperçu des stats
    public static function formatStats(array $stats): string
    {
        return collect($stats)
            ->map(fn($value, $key) => "$key: $value")
            ->join(', ');
    }

    // Méthode pour générer des stats équilibrées (moins aléatoire)
    public static function generateBalanced(int $level): array
    {
        $stats = self::getEmptyStats();
        $pointsPerCategory = floor(self::POINTS_BY_LEVEL[$level] / count(self::CATEGORIES));
        $remainingPoints = self::POINTS_BY_LEVEL[$level] - ($pointsPerCategory * count(self::CATEGORIES));

        // Distribution de base
        foreach (self::CATEGORIES as $category) {
            $stats[$category] = $pointsPerCategory;
        }

        // Distribution des points restants
        $categories = self::CATEGORIES;
        while ($remainingPoints > 0) {
            $category = $categories[array_rand($categories)];
            $stats[$category]++;
            $remainingPoints--;
        }

        return $stats;
    }

    // Méthode pour générer des stats spécialisées (focus sur certaines compétences)
    public static function generateSpecialized(int $level, array $focusCategories): array
    {
        if (empty($focusCategories) || count(array_intersect($focusCategories, self::CATEGORIES)) === 0) {
            throw new \InvalidArgumentException("Les catégories de focus doivent être valides");
        }

        $stats = self::getEmptyStats();
        $remainingPoints = self::POINTS_BY_LEVEL[$level];

        // 70% des points dans les catégories de focus
        $focusPoints = floor($remainingPoints * 0.7);
        $otherPoints = $remainingPoints - $focusPoints;

        // Distribution des points de focus
        while ($focusPoints > 0) {
            $category = $focusCategories[array_rand($focusCategories)];
            $stats[$category]++;
            $focusPoints--;
        }

        // Distribution des points restants
        $otherCategories = array_diff(self::CATEGORIES, $focusCategories);
        while ($otherPoints > 0) {
            $category = $otherCategories[array_rand($otherCategories)];
            $stats[$category]++;
            $otherPoints--;
        }

        return $stats;
    }
}
