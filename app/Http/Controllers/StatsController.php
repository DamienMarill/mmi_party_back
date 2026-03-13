<?php

namespace App\Http\Controllers;

use App\Enums\CardRarity;
use App\Models\CardInstance;
use App\Models\CardTemplate;
use App\Models\CardVersion;
use App\Models\Lootbox;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    /**
     * GET /api/stats
     * Retourne toutes les statistiques publiques.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'registrations' => $this->getRegistrationStats(),
            'global'        => $this->getGlobalStats(),
            'boosters_per_day' => $this->getBoostersPerDay(),
            'podium'        => $this->getPodium(),
        ]);
    }

    /**
     * GET /api/stats/me
     * Statistiques personnelles du joueur authentifié.
     */
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $totalCardVersions = CardVersion::count();

        $uniqueCards = CardInstance::where('user_id', $user->id)
            ->distinct('card_version_id')
            ->count('card_version_id');

        $totalCards = CardInstance::where('user_id', $user->id)->count();

        $totalBoosters = Lootbox::where('user_id', $user->id)->count();

        // Répartition par rareté des cartes uniques
        $rarityBreakdown = CardInstance::where('card_instances.user_id', $user->id)
            ->join('card_versions', 'card_instances.card_version_id', '=', 'card_versions.id')
            ->select('card_versions.rarity', DB::raw('COUNT(DISTINCT card_instances.card_version_id) as count'))
            ->groupBy('card_versions.rarity')
            ->get()
            ->mapWithKeys(fn($row) => [
                $row->rarity => [
                    'count' => $row->count,
                    'label' => CardRarity::from($row->rarity)->label(),
                ]
            ]);

        // Rang dans le classement global
        $rank = CardInstance::select('user_id', DB::raw('COUNT(DISTINCT card_version_id) as unique_cards'))
            ->groupBy('user_id')
            ->orderByDesc('unique_cards')
            ->get()
            ->search(fn($row) => $row->user_id === $user->id);

        // Conversion en rang de compétition standard
        $collectors = CardInstance::select('user_id', DB::raw('COUNT(DISTINCT card_version_id) as unique_cards'))
            ->groupBy('user_id')
            ->orderByDesc('unique_cards')
            ->get();
        $globalRank = null;
        $r = 1;
        foreach ($collectors as $i => $c) {
            if ($i > 0 && $c->unique_cards < $collectors[$i - 1]->unique_cards) {
                $r = $i + 1;
            }
            if ($c->user_id === $user->id) {
                $globalRank = $r;
                break;
            }
        }

        return response()->json([
            'unique_cards'      => $uniqueCards,
            'total_cards'       => $totalCards,
            'total_versions'    => $totalCardVersions,
            'completion_rate'   => $totalCardVersions > 0
                ? round(($uniqueCards / $totalCardVersions) * 100, 1)
                : 0,
            'total_boosters'    => $totalBoosters,
            'rarity_breakdown'  => $rarityBreakdown,
            'global_rank'       => $globalRank,
        ]);
    }

    /**
     * Nombre d'inscrits vs total par niveau MMI (basé sur CardTemplate).
     */
    private function getRegistrationStats(): array
    {
        $levels = [1, 2, 3];
        $result = [];

        foreach ($levels as $level) {
            $total = CardTemplate::where('type', 'student')
                ->where('level', $level)
                ->count();

            $registered = CardTemplate::where('type', 'student')
                ->where('level', $level)
                ->whereNotNull('base_user')
                ->count();

            $result[] = [
                'level' => $level,
                'label' => "MMI $level",
                'registered' => $registered,
                'total' => $total,
            ];
        }

        return $result;
    }

    /**
     * Métriques globales.
     */
    private function getGlobalStats(): array
    {
        return [
            'total_boosters' => Lootbox::count(),
            'total_cards' => CardInstance::count(),
            // Joueurs ayant ouvert au moins un booster dans les 7 derniers jours
            'active_players' => Lootbox::where('created_at', '>=', Carbon::now()->subDays(7))
                ->distinct('user_id')
                ->count('user_id'),
        ];
    }

    /**
     * Nombre de boosters ouverts par jour sur les 7 derniers jours.
     */
    private function getBoostersPerDay(): array
    {
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $count = Lootbox::whereDate('created_at', $date)->count();
            $days[] = [
                'date' => $date,
                'label' => Carbon::parse($date)->locale('fr')->isoFormat('ddd D'),
                'count' => $count,
            ];
        }

        return $days;
    }

    /**
     * Top 3 des collectionneurs (cartes uniques par card_version_id).
     */
    private function getPodium(): array
    {
        $topCollectors = CardInstance::select('user_id', DB::raw('COUNT(DISTINCT card_version_id) as unique_cards'))
            ->groupBy('user_id')
            ->orderByDesc('unique_cards')
            ->limit(10)
            ->get();

        $result = [];
        $rank = 1;

        foreach ($topCollectors as $i => $collector) {
            // Ranking par compétition standard (1,1,3,4...) : en cas d'égalité même rang, le suivant saute
            if ($i > 0 && $collector->unique_cards < $result[$i - 1]['unique_cards']) {
                $rank = $i + 1;
            }

            $user = User::with('mmii')->find($collector->user_id);
            $result[] = [
                'rank'         => $rank,
                'name'         => $user?->name ?? 'Inconnu',
                'unique_cards' => $collector->unique_cards,
                'mmii'         => $user?->mmii ? [
                    'shape'      => $user->mmii->shape,
                    'background' => $user->mmii->background,
                ] : null,
            ];
        }

        return $result;
    }
}

