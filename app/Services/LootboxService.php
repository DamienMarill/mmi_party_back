<?php

namespace App\Services;

use App\Enums\CardRarity;
use App\Enums\LootboxTypes;
use App\Models\CardVersion;
use App\Models\Lootbox;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class LootboxService
{

    public function generateLoot(int $slotIndex): CardVersion
    {
        $rarity = $this->rollRarity($slotIndex);

        return CardVersion::where('rarity', $rarity)
            ->inRandomOrder()
            ->firstOrFail();
    }

    private function rollRarity(int $slotIndex): CardRarity
    {
        $rates = config('app.loot_rate')[$slotIndex];
        $random = mt_rand() / mt_getrandmax();
        $cumulative = 0;

        foreach ($rates as $rarityValue => $drop) {
            $cumulative += $drop;

            if ($random <= $cumulative) {
                return CardRarity::from($rarityValue);
            }
        }

        return CardRarity::COMMON;
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
     */
    public function checkAvailability(string $userId): array
    {
        $now = Carbon::now();

        $availableSlots = $this->getAvailableSlotsInPeriod($now);

        $unusedSlots = [];
        $allSlotsInfo = [];

        foreach ($availableSlots as $slot) {
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

        $nextSlot = count($unusedSlots) > 0 ? $unusedSlots[0] : null;

        $currentTime = $now->format('H:i');
        $nextAvailable = $this->getNextAvailableTime($currentTime);

        // Si des slots sont dispos, utiliser le timestamp du prochain slot
        // Sinon, utiliser le prochain créneau calculé
        $nextAvailableDateTime = $nextSlot
            ? $nextSlot['timestamp']->toIso8601String()
            : $nextAvailable['dateTime']->toIso8601String();

        return [
            'available' => count($unusedSlots) > 0,
            'count' => count($unusedSlots),
            'nextSlot' => $nextSlot,
            'nextTime' => $nextAvailable['time'],
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

    private function getAvailableSlotsInPeriod(Carbon $now): array
    {
        $slots = [];

        foreach ($this->availableTimes as $slotTime) {
            $todaySlot = $now->copy()->setTimeFromTimeString($slotTime);

            if ($todaySlot <= $now) {
                $slots[] = [
                    'time' => $slotTime,
                    'timestamp' => $todaySlot,
                ];
            } else {
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

    private function getNextAvailableTime(string $currentTime): array
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

        $nextDateTime = Carbon::now()->setTimeFromTimeString($nextTime);
        if ($nextDateTime->lte(Carbon::now())) {
            $nextDateTime->addDay();
        }

        return [
            'time' => $nextTime,
            'dateTime' => $nextDateTime,
        ];
    }
}
