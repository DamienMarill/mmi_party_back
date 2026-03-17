<?php

namespace App\PromoCheckers;

use App\Contracts\PromoChecker;
use App\Models\Lootbox;
use App\Models\User;

class LootboxCountChecker implements PromoChecker
{
    /**
     * Check if user has opened at least $data['target'] lootboxes.
     */
    public function isFulfilled(User $user, array $data): bool
    {
        $target = $data['target'] ?? 0;

        $count = Lootbox::where('user_id', $user->id)->count();

        return $count >= $target;
    }
}
