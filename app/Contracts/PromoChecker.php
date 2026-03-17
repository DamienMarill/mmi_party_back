<?php

namespace App\Contracts;

use App\Models\User;

interface PromoChecker
{
    public function isFulfilled(User $user, array $data): bool;
}
