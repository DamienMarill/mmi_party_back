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
     * Logique : 
     * - Chaque slot (ex: 12:35, 18:35) génère un booster
     * - Un booster expire après 24h (remplacé par le prochain cycle du même slot)
     * - Maximum 2 boosters accumulables (un par slot)
     * 
     * Calcul : slots disponibles dans les dernières 24h - lootboxes ouvertes dans la même période
     */
    public function checkAvailability(string $userId): array
    {
        $now = Carbon::now();

        // Collecter tous les slots disponibles (passés dans les dernières 24h)
        $availableSlots = $this->getAvailableSlotsInPeriod($now);

        // Compter les lootboxes ouvertes depuis le plus ancien slot disponible
        $oldestSlot = collect($availableSlots)->min();

        $openedCount = 0;
        if ($oldestSlot) {
            $openedCount = Lootbox::where('user_id', $userId)
                ->where('type', LootboxTypes::QUOTIDIAN->value)
                ->where('created_at', '>=', $oldestSlot)
                ->count();
        }

        // Calcul : slots disponibles - lootboxes ouvertes
        $remainingCount = max(0, count($availableSlots) - $openedCount);

        $currentTime = $now->format('H:i');

        return [
            'available' => $remainingCount > 0,
            'count' => $remainingCount,
            'nextTime' => $this->getNextAvailableTime($currentTime),
            'debug' => [
                'now' => $now->format('Y-m-d H:i:s'),
                'availableSlots' => collect($availableSlots)->map(fn($s) => $s->format('Y-m-d H:i:s'))->toArray(),
                'oldestSlot' => $oldestSlot?->format('Y-m-d H:i:s'),
                'openedCount' => $openedCount,
                'remainingCount' => $remainingCount,
            ]
        ];
    }

    /**
     * Retourne tous les slots qui sont passés et encore valides (non expirés).
     * Un slot est valide s'il est passé ET n'a pas encore été remplacé par le prochain cycle.
     */
    private function getAvailableSlotsInPeriod(Carbon $now): array
    {
        $slots = [];

        foreach ($this->availableTimes as $slotTime) {
            // Le slot d'aujourd'hui (s'il est déjà passé)
            $todaySlot = $now->copy()->setTimeFromTimeString($slotTime);

            if ($todaySlot <= $now) {
                // Le slot est passé aujourd'hui, donc disponible
                $slots[] = $todaySlot;
            } else {
                // Le slot n'est pas encore passé aujourd'hui
                // On prend celui d'hier (qui n'a pas encore été remplacé)
                $yesterdaySlot = $todaySlot->copy()->subDay();
                $slots[] = $yesterdaySlot;
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
