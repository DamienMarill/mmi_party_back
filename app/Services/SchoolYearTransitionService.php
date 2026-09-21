<?php

namespace App\Services;

use App\Enums\UserGroups;
use App\Models\SchoolYearTransition;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SchoolYearTransitionService
{
    public function execute(string $schoolYear, array $targets, ?User $executor = null): array
    {
        $this->guardSchoolYearFormat($schoolYear);

        if (SchoolYearTransition::where('school_year', $schoolYear)->exists()) {
            throw new DomainException("La transition {$schoolYear} a déjà été exécutée.");
        }

        return DB::transaction(function () use ($schoolYear, $targets, $executor): array {
            User::query()->where('groupe', UserGroups::MMI3->value)->update(['groupe' => UserGroups::ALUMNI->value]);
            User::query()->where('groupe', UserGroups::MMI2->value)->update(['groupe' => UserGroups::MMI3->value]);
            User::query()->where('groupe', UserGroups::MMI1->value)->update(['groupe' => UserGroups::MMI2->value]);

            $applied = [
                UserGroups::MMI1->value => $this->syncBotsForTargetPopulation(UserGroups::MMI1, (int) ($targets['mmi1'] ?? 0)),
                UserGroups::MMI2->value => $this->syncBotsForTargetPopulation(UserGroups::MMI2, (int) ($targets['mmi2'] ?? 0)),
                UserGroups::MMI3->value => $this->syncBotsForTargetPopulation(UserGroups::MMI3, (int) ($targets['mmi3'] ?? 0)),
            ];

            $deletedAlumniBots = $this->botQuery()
                ->where('groupe', UserGroups::ALUMNI->value)
                ->delete();

            SchoolYearTransition::create([
                'school_year' => $schoolYear,
                'executed_by' => $executor?->id,
                'bot_targets' => $applied,
                'executed_at' => now(),
            ]);

            return [
                'school_year' => $schoolYear,
                'bot_targets' => $applied,
                'deleted_alumni_bots' => $deletedAlumniBots,
            ];
        });
    }

    private function syncBotsForTargetPopulation(UserGroups $group, int $targetPopulation): array
    {
        $targetPopulation = max(0, $targetPopulation);
        $botQuery = $this->botQuery()->where('groupe', $group->value);
        $botBefore = $botQuery->count();
        $totalBefore = User::query()->where('groupe', $group->value)->count();
        $realCount = $totalBefore - $botBefore;
        $botTarget = max(0, $targetPopulation - $realCount);
        $delta = $botTarget - $botBefore;

        if ($delta > 0) {
            $this->createBots($group, $delta);
        } elseif ($delta < 0) {
            $botQuery
                ->orderByDesc('created_at')
                ->limit(abs($delta))
                ->delete();
        }

        $botAfter = $this->botQuery()->where('groupe', $group->value)->count();

        return [
            'target_population' => $targetPopulation,
            'real_count' => $realCount,
            'bot_before' => $botBefore,
            'bot_after' => $botAfter,
            'total_after' => $realCount + $botAfter,
        ];
    }

    private function createBots(UserGroups $group, int $count): void
    {
        for ($index = 0; $index < $count; $index++) {
            User::create([
                'name' => fake()->name(),
                'email' => 'bot+'.Str::ulid().'@mmi.local',
                'um_email' => null,
                'password' => Str::random(40),
                'groupe' => $group,
                'moodle_id' => null,
                'moodle_username' => null,
                'email_verified_at' => now(),
                'is_admin' => false,
            ]);
        }
    }

    private function botQuery(): Builder
    {
        return User::query()
            ->whereNull('moodle_id')
            ->where('is_admin', false);
    }

    private function guardSchoolYearFormat(string $schoolYear): void
    {
        if (!preg_match('/^(?<start>\d{4})-(?<end>\d{4})$/', $schoolYear, $matches)) {
            throw new DomainException('Le format attendu est YYYY-YYYY (ex: 2026-2027).');
        }

        if (((int) $matches['start']) + 1 !== (int) $matches['end']) {
            throw new DomainException('La seconde année doit être égale à la première + 1.');
        }
    }
}
