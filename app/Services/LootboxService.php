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

        // On récupère toutes les lootboxes des dernières 24h
        $recentLootboxes = Lootbox::where('user_id', $userId)
            ->where('type', LootboxTypes::QUOTIDIAN->value)
            ->whereBetween('created_at', [$periodStart, $now])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calcul des créneaux disponibles sur la période de 24h
        $availableSlots = [];
        $currentTime = $now->format('H:i');

        // Créneaux d'hier (après periodStart)
        foreach ($this->availableTimes as $time) {
            $slotTime = $periodStart->copy()->setTimeFromTimeString($time);
            if ($slotTime > $periodStart && $slotTime <= $now) {
                $availableSlots[] = $slotTime;
            }
        }

        // Créneaux d'aujourd'hui (jusqu'à now)
        foreach ($this->availableTimes as $time) {
            $slotTime = $now->copy()->setTimeFromTimeString($time);
            if ($slotTime <= $now) {
                $availableSlots[] = $slotTime;
            }
        }

        // Nombre de lootboxes qu'on aurait dû pouvoir ouvrir
        $totalAvailable = count($availableSlots);

        // Nombre de lootboxes déjà ouvertes
        $openedCount = $recentLootboxes->count();

        // Calcul des lootboxes encore disponibles
        $remainingCount = max(0, $totalAvailable - $openedCount);

        return [
            'available' => $remainingCount > 0,
            'count' => $remainingCount,
            'nextTime' => $this->getNextAvailableTime($currentTime),
            'debug' => [
                'totalAvailable' => $totalAvailable,
                'openedCount' => $openedCount,
                'periodStart' => $periodStart->format('Y-m-d H:i:s'),
                'now' => $now->format('Y-m-d H:i:s'),
                'availableSlots' => $availableSlots,
                'recentLootboxes' => $recentLootboxes->map(fn($box) => $box->created_at->format('Y-m-d H:i:s'))
            ]
        ];
    }

    private function handleNoRecentOpening(Carbon $now): array
    {
        $currentTime = $now->format('H:i');
        $availableSlots = [];

        // On vérifie les créneaux de la veille
        foreach ($this->availableTimes as $time) {
            $availableSlots[] = $time;
        }

        // On ajoute les créneaux d'aujourd'hui déjà passés
        foreach ($this->availableTimes as $time) {
            if ($this->timeToMinutes($time) <= $this->timeToMinutes($currentTime)) {
                $availableSlots[] = $time;
            }
        }

        return [
            'available' => true,
            'count' => min(count($availableSlots), 2), // Maximum 2 lootboxes stockables
            'nextTime' => $this->getNextAvailableTime($currentTime),
            'reason' => null
        ];
    }

    private function calculateAvailableLootboxes(Carbon $lastOpening, Carbon $now): array
    {
        $currentTime = $now->format('H:i');
        $availableSlots = [];

        // On regarde chaque créneau depuis la dernière ouverture
        foreach ($this->availableTimes as $time) {
            $slotTime = $this->createSlotTime($lastOpening, $time);

            // Si le créneau est entre la dernière ouverture et maintenant
            if ($slotTime > $lastOpening && $slotTime <= $now) {
                $availableSlots[] = $time;
            }
        }

        // On vérifie aussi les créneaux du jour d'avant si on est encore dans la fenêtre de 24h
        if ($lastOpening->diffInHours($now) > 12) {
            foreach ($this->availableTimes as $time) {
                $slotTime = $this->createSlotTime($lastOpening->copy()->subDay(), $time);
                if ($slotTime > $lastOpening->copy()->subHours(24) && $slotTime <= $now) {
                    $availableSlots[] = $time;
                }
            }
        }

        return [
            'available' => !empty($availableSlots),
            'count' => min(count($availableSlots), 2),
            'nextTime' => $this->getNextAvailableTime($currentTime),
            'reason' => empty($availableSlots) ? 'no_available_slots' : null
        ];
    }

    private function createSlotTime(Carbon $baseTime, string $slotTime): Carbon
    {
        [$hours, $minutes] = explode(':', $slotTime);
        return $baseTime->copy()->setTime((int)$hours, (int)$minutes);
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
            $diff = $targetMinutes - $currentMinutes;

            if ($diff <= 0) {
                $diff += 24 * 60;
            }

            if ($diff < $minDiff) {
                $minDiff = $diff;
                $nextTime = $time;
            }
        }

        return $nextTime;
    }
}
