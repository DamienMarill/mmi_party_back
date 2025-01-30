<?php

namespace App\Services;

use App\Enums\LootboxTypes;
use App\Models\CardTemplate;
use App\Models\CardVersion;
use App\Models\Lootbox;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class LootboxService
{

    public function generateLoot(int $slotIndex): CardVersion
    {
        // Étape 1: Sélection du template
        $template = $this->selectCardTemplate($slotIndex);

        // Étape 2: Sélection de la version
        return $this->selectCardVersion($template);
    }

    private function selectCardTemplate(int $slotIndex): CardTemplate
    {
        $dropRates = config('app.loot_rate')[$slotIndex];
        $random = mt_rand() / mt_getrandmax();
        $cumulativeProb = 0;

        foreach ($dropRates as $rate) {
            $cumulativeProb += $rate['drop'];

            if ($random <= $cumulativeProb) {
                return CardTemplate::where('type', $rate['type'])
                    ->where(function ($query) use ($rate) {
                        $query->where('level', $rate['level'])
                            ->orWhereNull('level');
                    })
                    ->inRandomOrder()
                    ->firstOrFail();
            }
        }

        // Fallback au cas où (ne devrait jamais arriver si les taux sont bien configurés)
        throw new \RuntimeException('No template selected');
    }

    private function selectCardVersion(CardTemplate $template): CardVersion
    {
        $versions = $template->cardVersions()->get();

        if ($versions->isEmpty()) {
            throw new \RuntimeException('No versions available for template');
        }

        if ($versions->count() === 1) {
            return $versions->first();
        }

        // Calcul des taux normalisés
        $totalWeight = $versions->sum(function ($version) {
            return $version->rarity->dropRate();
        });

        $random = mt_rand() / mt_getrandmax() * $totalWeight;
        $cumulativeProb = 0;

        foreach ($versions as $version) {
            $cumulativeProb += $version->rarity->dropRate();

            if ($random <= $cumulativeProb) {
                return $version;
            }
        }

        // Fallback sur la première version (ne devrait jamais arriver)
        return $versions->first();
    }

    public function generateLootbox(): \Illuminate\Support\Collection
    {
        return collect(range(0, 4))->map(function ($slot) {
            return $this->generateLoot($slot);
        });
    }

    private array $availableTimes;
    private int $availabilityPeriod;

    public function __construct()
    {
        $this->availableTimes = Config::get('app.lootbox_times', []);
        $this->availabilityPeriod = Config::get('app.lootbox_avaibility', 24);
    }

    public function checkAvailability(string $userId): array
    {
        $now = Carbon::now();
        $periodStart = $now->copy()->subHours($this->availabilityPeriod);

        // Compte les boosters quotidiens ouverts pendant la période
        $openedCount = Lootbox::where('user_id', $userId)
            ->where('type', LootboxTypes::QUOTIDIAN->value)
            ->whereBetween('created_at', [$periodStart, $now])
            ->count();

        Log::info('Opened count: ' . $openedCount.' '.$periodStart.' '.$now);

        // Si le joueur a ouvert moins de boosters que d'horaires disponibles
        if ($openedCount < count($this->availableTimes)) {
            // Vérifie si on est dans une plage horaire valide
            $currentTime = $now->format('H:i');
            $isTimeValid = false;
            $nextTime = null;

            foreach ($this->availableTimes as $time) {
                if ($this->isWithinTimeRange($currentTime, $time)) {
                    $isTimeValid = true;
                    break;
                }
            }

            if (!$isTimeValid) {
                $nextTime = $this->getNextAvailableTime($currentTime);
            }

            return [
                'available' => $isTimeValid,
                'nextTime' => $nextTime,
                'reason' => $isTimeValid ? null : 'wrong_time'
            ];
        }

        // Le joueur a déjà ouvert tous ses boosters
        return [
            'available' => false,
            'nextTime' => $this->getNextRefreshTime($periodStart),
            'reason' => 'max_reached'
        ];
    }

    private function isWithinTimeRange(string $current, string $targetTime): bool
    {
        $currentMinutes = $this->timeToMinutes($current);
        $targetMinutes = $this->timeToMinutes($targetTime);

        // Donne une fenêtre de 30 minutes pour ouvrir le booster
        return abs($currentMinutes - $targetMinutes) <= 15;
    }

    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);
        return (int)$hours * 60 + (int)$minutes;
    }

    private function getNextAvailableTime(string $currentTime): string
    {
        $currentMinutes = $this->timeToMinutes($currentTime);
        $nextTime = null;
        $minDiff = PHP_INT_MAX;

        foreach ($this->availableTimes as $time) {
            $targetMinutes = $this->timeToMinutes($time);

            // Si l'heure est déjà passée, on ajoute 24h
            if ($targetMinutes <= $currentMinutes) {
                $targetMinutes += 24 * 60;
            }

            $diff = $targetMinutes - $currentMinutes;
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $nextTime = $time;
            }
        }

        return $nextTime;
    }

    private function getNextRefreshTime(Carbon $periodStart): string
    {
        // Retourne l'heure à laquelle le prochain booster sera disponible
        return $periodStart->addHours($this->availabilityPeriod)->format('H:i');
    }
}
