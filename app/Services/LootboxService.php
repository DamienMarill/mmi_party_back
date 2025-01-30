<?php

namespace App\Services;

use App\Models\CardTemplate;
use App\Models\CardVersion;
use App\Models\Lootbox;
use App\Models\User;
use Carbon\Carbon;

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

    public function canOpenLootbox(User $user): array
    {
        $now = now();
        $result = [];

        // On récupère les dernières lootboxes créées pour chaque horaire
        foreach (config('app.lootbox_times') as $time) {
            $targetTime = Carbon::createFromFormat('H:i', $time);
            $todayTarget = Carbon::today()->setHour($targetTime->hour)->setMinute($targetTime->minute);

            // Si on n'a pas encore atteint l'heure aujourd'hui, on check celle d'hier
            $referenceDate = $now->lt($todayTarget)
                ? $todayTarget->subDay()
                : $todayTarget;

            // On cherche la dernière lootbox créée pour cet horaire
            $lootbox = Lootbox::where('created_at', '>=', $referenceDate->copy()->subDay())
                ->where('created_at', '<=', $referenceDate->copy()->addMinutes(5))
                ->latest()
                ->first();

            if (!$lootbox) {
                // Pas de lootbox créée pour cet horaire
                $result[$time] = [
                    'available' => $now->gte($referenceDate),
                    'message' => 'Lootbox pas encore créée',
                    'next_reset' => null
                ];
                continue;
            }

            // On vérifie si l'utilisateur a déjà ouvert cette lootbox
            $hasOpened = $user->openedLootboxes()
                ->where('lootbox_id', $lootbox->id)
                ->exists();

            // On vérifie si la lootbox est encore disponible
            $expiresAt = $lootbox->created_at->addHours(config('app.lootbox_avaibility'));
            $isExpired = $now->gt($expiresAt);

            $result[$time] = [
                'available' => !$hasOpened && !$isExpired && $now->gte($referenceDate),
                'message' => $this->getLootboxMessage($hasOpened, $isExpired),
                'next_reset' => $this->getNextResetTime($time)
            ];
        }

        return $result;
    }

    private function getLootboxMessage(bool $hasOpened, bool $isExpired): string
    {
        if ($hasOpened) return 'Vous avez déjà ouvert cette lootbox';
        if ($isExpired) return 'Cette lootbox a expiré';
        return 'Lootbox disponible';
    }

    private function getNextResetTime(string $time): Carbon
    {
        $now = now();
        $targetTime = Carbon::createFromFormat('H:i', $time);
        $nextReset = Carbon::today()
            ->setHour($targetTime->hour)
            ->setMinute($targetTime->minute);

        if ($now->gt($nextReset)) {
            $nextReset->addDay();
        }

        return $nextReset;
    }

    public function getNextLootbox(User $user): ?array
    {
        $status = $this->canOpenLootbox($user);
        $now = now();

        // On filtre les lootboxes disponibles
        $availableLootboxes = collect($status)->filter(function ($lootbox) {
            return $lootbox['available'];
        });

        if ($availableLootboxes->isNotEmpty()) {
            // Si on a des lootboxes disponibles, on prend celle qui expire le plus tôt
            $nextTime = $availableLootboxes->keys()->min();

            return [
                'time' => $nextTime,
                'status' => $status[$nextTime],
                'type' => 'available',
                'message' => 'Lootbox disponible à ouvrir',
                'remaining_time' => $this->calculateRemainingTime($nextTime)
            ];
        }

        // Sinon on cherche la prochaine lootbox à s'ouvrir
        $nextReset = collect($status)
            ->map(fn ($lootbox) => $lootbox['next_reset'])
            ->filter()
            ->min();

        if ($nextReset) {
            $nextTime = $nextReset->format('H:i');

            return [
                'time' => $nextTime,
                'status' => $status[$nextTime],
                'type' => 'upcoming',
                'message' => 'Prochaine lootbox disponible',
                'remaining_time' => $now->diffForHumans($nextReset)
            ];
        }

        return null;
    }

    private function calculateRemainingTime(string $time): string
    {
        $now = now();
        $lootbox = Lootbox::where('created_at', '>=', now()->subDay())
            ->where('created_at', '<=', now())
            ->whereRaw("DATE_FORMAT(created_at, '%H:%i') = ?", [$time])
            ->latest()
            ->first();

        if (!$lootbox) {
            return 'Non disponible';
        }

        $expiresAt = $lootbox->created_at->addHours(config('app.lootbox_avaibility'));
        return $now->diffForHumans($expiresAt);
    }
}
