<?php

namespace App\Http\Controllers;

use App\Enums\RoomStatus;
use App\Events\TradeCancelled;
use App\Events\TradeCompleted;
use App\Events\TradeStateUpdated;
use App\Models\CardInstance;
use App\Models\HubRoom;
use App\Models\TradeLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TradeController extends Controller
{
    private function getRoomAndCheckAccess(string $roomId, $user): ?HubRoom
    {
        $room = HubRoom::find($roomId);
        if (!$room || !$room->hasPlayer($user) || $room->status !== RoomStatus::ACTIVE) {
            return null;
        }
        return $room;
    }

    private function getTradeState(HubRoom $room): array
    {
        return $room->metadata ?? [
            'player_one_card_id' => null,
            'player_two_card_id' => null,
            'player_one_validated' => false,
            'player_two_validated' => false,
            'player_one_accepted' => false,
            'player_two_accepted' => false,
        ];
    }

    private function getHydratedState(array $state): array
    {
        $hydrated = $state;
        
        if (!empty($state['player_one_card_id'])) {
            $hydrated['player_one_card'] = CardInstance::with('cardVersion')->find($state['player_one_card_id']);
        } else {
            $hydrated['player_one_card'] = null;
        }

        if (!empty($state['player_two_card_id'])) {
            $hydrated['player_two_card'] = CardInstance::with('cardVersion')->find($state['player_two_card_id']);
        } else {
            $hydrated['player_two_card'] = null;
        }

        return $hydrated;
    }

    private function saveTradeState(HubRoom $room, array $state): void
    {
        $room->metadata = $state;
        $room->save();

        $broadcastState = $this->getHydratedState($state);
        broadcast(new TradeStateUpdated($room->id, $broadcastState))->toOthers();
    }

    /**
     * POST /api/trade/{roomId}/select-card
     */
    public function selectCard(string $roomId, Request $request): JsonResponse
    {
        $request->validate(['card_instance_id' => 'required|uuid']);
        
        $user = $request->user();
        $room = $this->getRoomAndCheckAccess($roomId, $user);
        
        if (!$room) {
            return response()->json(['error' => 'Room invalide ou non autorisée'], 403);
        }

        $cardId = $request->input('card_instance_id');
        $card = CardInstance::where('id', $cardId)->where('user_id', $user->id)->first();
        
        if (!$card) {
            return response()->json(['error' => 'Cette carte ne vous appartient pas'], 403);
        }

        $state = $this->getTradeState($room);

        // Si l'utilisateur a déjà validé, il ne peut plus changer sa carte
        $isPlayerOne = $user->id === $room->player_one_id;
        if (($isPlayerOne && $state['player_one_validated']) || (!$isPlayerOne && $state['player_two_validated'])) {
            return response()->json(['error' => 'Vous avez déjà validé votre choix'], 400);
        }

        if ($isPlayerOne) {
            $state['player_one_card_id'] = $cardId;
        } else {
            $state['player_two_card_id'] = $cardId;
        }

        $this->saveTradeState($room, $state);

        return response()->json(['message' => 'Carte sélectionnée', 'state' => $this->getHydratedState($state)]);
    }

    /**
     * POST /api/trade/{roomId}/validate
     */
    public function validateSelection(string $roomId, Request $request): JsonResponse
    {
        $user = $request->user();
        $room = $this->getRoomAndCheckAccess($roomId, $user);
        
        if (!$room) {
            return response()->json(['error' => 'Room invalide ou non autorisée'], 403);
        }

        $state = $this->getTradeState($room);
        $isPlayerOne = $user->id === $room->player_one_id;

        // Vérifier que le joueur a bien sélectionné une carte
        $myCardId = $isPlayerOne ? $state['player_one_card_id'] : $state['player_two_card_id'];
        if (!$myCardId) {
            return response()->json(['error' => 'Veuillez d\'abord sélectionner une carte'], 400);
        }

        // Règle Métier : Si l'autre a déjà validé, on vérifie la rareté
        $otherCardId = $isPlayerOne ? $state['player_two_card_id'] : $state['player_one_card_id'];
        $otherValidated = $isPlayerOne ? $state['player_two_validated'] : $state['player_one_validated'];

        if ($otherValidated && $otherCardId) {
            $myCard = CardInstance::with('cardVersion')->find($myCardId);
            $otherCard = CardInstance::with('cardVersion')->find($otherCardId);

            if ($myCard->cardVersion->rarity !== $otherCard->cardVersion->rarity) {
                return response()->json(['error' => 'Les cartes doivent être de même rareté'], 400);
            }
        }

        if ($isPlayerOne) {
            $state['player_one_validated'] = true;
        } else {
            $state['player_two_validated'] = true;
        }

        $this->saveTradeState($room, $state);

        return response()->json(['message' => 'Choix validé', 'state' => $this->getHydratedState($state)]);
    }

    /**
     * POST /api/trade/{roomId}/accept
     */
    public function acceptTrade(string $roomId, Request $request): JsonResponse
    {
        $user = $request->user();
        $room = $this->getRoomAndCheckAccess($roomId, $user);
        
        if (!$room) {
            return response()->json(['error' => 'Room invalide ou non autorisée'], 403);
        }

        $state = $this->getTradeState($room);
        
        if (!$state['player_one_validated'] || !$state['player_two_validated']) {
            return response()->json(['error' => 'Les deux joueurs doivent définir leur carte d\'abord'], 400);
        }

        $isPlayerOne = $user->id === $room->player_one_id;

        // Règle métier : Vérifier les 5 échanges/jour (reset à 4h mat)
        $todayReset = Carbon::now()->subHours(4)->startOfDay()->addHours(4);
        $tradeCount = TradeLog::where(function($q) use ($user) {
            $q->where('user_1_id', $user->id)->orWhere('user_2_id', $user->id);
        })->where('created_at', '>=', $todayReset)->count();

        if ($tradeCount >= 5) {
            return response()->json(['error' => 'Vous avez atteint la limite de 5 échanges par jour (reset à 4h)'], 400);
        }

        // Enregistrer l'acceptation
        if ($isPlayerOne) {
            $state['player_one_accepted'] = true;
        } else {
            $state['player_two_accepted'] = true;
        }

        $this->saveTradeState($room, $state);

        // Si les deux ont accepté, on exécute l'échange
        if ($state['player_one_accepted'] && $state['player_two_accepted']) {
            return $this->executeTrade($room, $state);
        }

        return response()->json(['message' => 'Échange accepté, en attente de l\'autre joueur', 'state' => $this->getHydratedState($state)]);
    }

    /**
     * Exécute l'échange réel en BDD
     */
    private function executeTrade(HubRoom $room, array $state): JsonResponse
    {
        DB::beginTransaction();
        try {
            $card1 = CardInstance::find($state['player_one_card_id']);
            $card2 = CardInstance::find($state['player_two_card_id']);

            if (!$card1 || !$card2) {
                throw new \Exception('Une des cartes n\'existe plus');
            }

            // Swap des users
            $card1->user_id = $room->player_two_id;
            $card2->user_id = $room->player_one_id;
            
            $card1->save();
            $card2->save();

            // Création du log
            TradeLog::create([
                'user_1_id' => $room->player_one_id,
                'user_2_id' => $room->player_two_id,
                'card_instance_1_id' => $card1->id,
                'card_instance_2_id' => $card2->id,
            ]);

            // Fermeture de la room
            $room->status = RoomStatus::COMPLETED;
            $room->save();

            DB::commit();

            broadcast(new TradeCompleted($room->id))->toOthers();

            return response()->json(['message' => 'Échange finalisé avec succès', 'state' => $this->getHydratedState($state)]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de l\'échange: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/trade/{roomId}/cancel
     */
    public function cancelTrade(string $roomId, Request $request): JsonResponse
    {
        $user = $request->user();
        $room = $this->getRoomAndCheckAccess($roomId, $user);
        
        if (!$room) {
            return response()->json(['error' => 'Room invalide ou non autorisée'], 403);
        }

        $room->status = RoomStatus::ABANDONED;
        $room->save();

        broadcast(new TradeCancelled($room->id))->toOthers();

        return response()->json(['message' => 'Échange annulé']);
    }
}
