<?php

namespace App\Http\Controllers;

use App\Models\CardInstance;
use App\Models\CardTemplate;
use App\Models\Lootbox;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

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
            'global' => $this->getGlobalStats(),
            'boosters_per_day' => $this->getBoostersPerDay(),
            'podium' => $this->getPodium(),
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
            'active_players' => User::where('updated_at', '>=', Carbon::now()->subDays(7))->count(),
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
            ->limit(3)
            ->with('user:id,name')
            ->get();

        // Charger les users séparément pour éviter les problèmes avec le with + groupBy
        $result = [];
        foreach ($topCollectors as $index => $collector) {
            $user = User::find($collector->user_id);
            $result[] = [
                'rank' => $index + 1,
                'name' => $user?->name ?? 'Inconnu',
                'unique_cards' => $collector->unique_cards,
            ];
        }

        return $result;
    }
}
