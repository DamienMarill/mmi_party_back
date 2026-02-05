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

    /**
     * Vérifie la disponibilité des boosters pour un utilisateur.
     * 
     * Nouvelle logique avec timestamp-based slot tracking:
     * - Chaque slot (ex: 12:35, 18:35) génère un booster avec un timestamp précis
     * - Un slot est utilisé si une lootbox existe avec slot_used_at = timestamp du slot
     * - Un slot expire après 24h (remplacé par le prochain cycle du même slot)
     * - Maximum 2 boosters accumulables (un par slot)
     */
    public function checkAvailability(string $userId): array
    {
        $now = Carbon::now();

        // Collecter tous les slots disponibles avec leurs timestamps
        $availableSlots = $this->getAvailableSlotsInPeriod($now);

        // Pour chaque slot, vérifier s'il a déjà été utilisé
        $unusedSlots = [];
        $allSlotsInfo = [];

        foreach ($availableSlots as $slot) {
            // Chercher une lootbox qui a utilisé CE slot précisément
            $existingLootbox = Lootbox::where('user_id', $userId)
                ->where('type', LootboxTypes::QUOTIDIAN)
                ->where('slot_used_at', $slot['timestamp'])
                ->first();

            $isUsed = $existingLootbox !== null;

            $allSlotsInfo[] = [
                'time' => $slot['time'],
                'timestamp' => $slot['timestamp']->toIso8601String(),
                'used' => $isUsed,
            ];

            if (!$isUsed) {
                $unusedSlots[] = $slot;
            }
        }

        // Calculer le prochain slot disponible (pour le compte à rebours)
        $nextSlot = count($unusedSlots) > 0 ? $unusedSlots[0] : null;
        $nextAvailableDateTime = $nextSlot ? $nextSlot['timestamp']->toIso8601String() : null;

        $currentTime = $now->format('H:i');

        return [
            'available' => count($unusedSlots) > 0,
            'count' => count($unusedSlots),
            'nextSlot' => $nextSlot,
            'nextTime' => $this->getNextAvailableTime($currentTime),
            'nextAvailableDateTime' => $nextAvailableDateTime,
            'slotsInfo' => $allSlotsInfo,
            'debug' => [
                'now' => $now->format('Y-m-d H:i:s'),
                'availableSlots' => array_map(fn($s) => [
                    'time' => $s['time'],
                    'timestamp' => $s['timestamp']->format('Y-m-d H:i:s'),
                ], $availableSlots),
                'unusedSlots' => array_map(fn($s) => [
                    'time' => $s['time'],
                    'timestamp' => $s['timestamp']->format('Y-m-d H:i:s'),
                ], $unusedSlots),
            ]
        ];
    }

    /**
     * Retourne tous les slots qui sont passés et encore valides (non expirés).
     * Un slot est valide s'il est passé ET n'a pas encore été remplacé par le prochain cycle.
     * 
     * @return array Array de ['time' => 'HH:mm', 'timestamp' => Carbon]
     */
    private function getAvailableSlotsInPeriod(Carbon $now): array
    {
        $slots = [];

        foreach ($this->availableTimes as $slotTime) {
            // Le slot d'aujourd'hui (s'il est déjà passé)
            $todaySlot = $now->copy()->setTimeFromTimeString($slotTime);

            if ($todaySlot <= $now) {
                // Le slot est passé aujourd'hui, donc disponible
                $slots[] = [
                    'time' => $slotTime,
                    'timestamp' => $todaySlot,
                ];
            } else {
                // Le slot n'est pas encore passé aujourd'hui
                // On prend celui d'hier (qui n'a pas encore été remplacé)
                $yesterdaySlot = $todaySlot->copy()->subDay();
                $slots[] = [
                    'time' => $slotTime,
                    'timestamp' => $yesterdaySlot,
                ];
            }
        }

        return $slots;
    }

    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);
        return (int) $hours * 60 + (int) $minutes;
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
