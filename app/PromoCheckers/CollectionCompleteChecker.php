<?php

namespace App\PromoCheckers;

use App\Contracts\PromoChecker;
use App\Models\CardInstance;
use App\Models\User;

class CollectionCompleteChecker implements PromoChecker
{
    /**
     * Check if user owns at least $data['target'] distinct card versions.
     */
    public function isFulfilled(User $user, array $data): bool
    {
        $target = $data['target'] ?? 0;

        $distinctVersions = CardInstance::where('user_id', $user->id)
            ->distinct('card_version_id')
            ->count('card_version_id');

        return $distinctVersions >= $target;
    }
}
