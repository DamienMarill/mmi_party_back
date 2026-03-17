<?php

namespace App\Services;

use App\Contracts\PromoChecker;
use App\Models\CardInstance;
use App\Models\PromoCondition;
use App\Models\PromoUnlock;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PromoService
{
    /**
     * Check all active promo conditions for a user and unlock eligible ones.
     *
     * @return Collection<int, PromoCondition> Newly unlocked promo conditions (with cardVersion loaded)
     */
    public function checkAndUnlock(User $user): Collection
    {
        $now = Carbon::now();

        // Get active promo conditions not yet unlocked by this user
        $conditions = PromoCondition::where('active', true)
            ->whereDoesntHave('unlocks', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with('cardVersion')
            ->get();

        $newlyUnlocked = collect();

        foreach ($conditions as $condition) {
            // Check starts_at constraint
            if ($condition->starts_at && $now->lt($condition->starts_at)) {
                continue;
            }

            // Check ends_at constraint
            if ($condition->ends_at && $now->gt($condition->ends_at)) {
                continue;
            }

            $fulfilled = false;

            if ($condition->condition_type === 'date_range') {
                // For date_range type, condition is met if we are within the date range
                // The starts_at/ends_at check above already validates this
                $fulfilled = true;
            } else {
                // Class-based checker: condition_type is a FQCN
                $fulfilled = $this->evaluateClassChecker(
                    $condition->condition_type,
                    $user,
                    $condition->condition_data ?? []
                );
            }

            if ($fulfilled) {
                try {
                    DB::transaction(function () use ($user, $condition, $now, $newlyUnlocked) {
                        // Create the promo unlock record
                        PromoUnlock::create([
                            'user_id' => $user->id,
                            'promo_condition_id' => $condition->id,
                            'unlocked_at' => $now,
                        ]);

                        // Create the card instance for the user
                        CardInstance::create([
                            'card_version_id' => $condition->card_version_id,
                            'lootbox_id' => null,
                            'user_id' => $user->id,
                        ]);

                        $newlyUnlocked->push($condition);
                    });
                } catch (\Throwable $e) {
                    Log::error('Failed to unlock promo condition', [
                        'promo_condition_id' => $condition->id,
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Load cardVersion with template for the response
        $newlyUnlocked->load('cardVersion.cardTemplate');

        return $newlyUnlocked;
    }

    /**
     * Evaluate a class-based promo checker.
     */
    private function evaluateClassChecker(string $className, User $user, array $data): bool
    {
        try {
            if (! class_exists($className)) {
                Log::warning('Promo checker class not found', ['class' => $className]);

                return false;
            }

            $checker = app($className);

            if (! $checker instanceof PromoChecker) {
                Log::warning('Promo checker class does not implement PromoChecker interface', [
                    'class' => $className,
                ]);

                return false;
            }

            return $checker->isFulfilled($user, $data);
        } catch (\Throwable $e) {
            Log::error('Error evaluating promo checker', [
                'class' => $className,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
