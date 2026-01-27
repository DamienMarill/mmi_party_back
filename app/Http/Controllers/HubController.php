<?php

namespace App\Http\Controllers;

use App\Enums\HubType;
use App\Enums\InvitationStatus;
use App\Models\HubInvitation;
use App\Models\HubRoom;
use App\Models\User;
use App\Services\HubService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HubController extends Controller
{
    public function __construct(
        private HubService $hubService
    ) {
    }

    /**
     * GET /hub/{type}/players
     * Retourne la liste des joueurs en ligne sur ce hub
     * Note: La liste réelle est gérée par Presence Channel côté frontend
     * Cette route retourne un placeholder pour l'instant
     */
    public function onlinePlayers(string $type): JsonResponse
    {
        // La liste des joueurs en ligne est gérée par les Presence Channels
        // Cette route pourrait être utilisée pour un fallback ou pour récupérer
        // des infos supplémentaires sur les joueurs
        return response()->json([
            'message' => 'Use Presence Channel for real-time player list',
            'hub_type' => $type,
        ]);
    }

    /**
     * POST /hub/{type}/invite/{userId}
     * Crée une invitation et la broadcast au destinataire
     */
    public function sendInvitation(string $type, string $userId, Request $request): JsonResponse
    {
        $hubType = HubType::from($type);
        $sender = $request->user();
        $receiver = User::find($userId);

        if (!$receiver) {
            return response()->json(['error' => 'Joueur introuvable'], 404);
        }

        // Vérifier si le sender peut inviter
        $canInvite = $this->hubService->canInvite($sender, $receiver, $hubType);
        if (!$canInvite['can']) {
            return response()->json(['error' => $canInvite['reason']], 400);
        }

        $invitation = $this->hubService->createInvitation($sender, $receiver, $hubType);

        return response()->json([
            'invitation' => [
                'id' => $invitation->id,
                'type' => $invitation->type->value,
                'receiver' => [
                    'id' => $receiver->id,
                    'name' => $receiver->name,
                ],
                'status' => $invitation->status->value,
                'expires_at' => $invitation->expires_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * GET /hub/{type}/invitations
     * Retourne les invitations pending reçues par l'utilisateur
     */
    public function myInvitations(string $type, Request $request): JsonResponse
    {
        $hubType = HubType::from($type);
        $user = $request->user();

        $invitations = $this->hubService->getPendingInvitations($user, $hubType);

        return response()->json([
            'invitations' => $invitations->map(fn($inv) => [
                'id' => $inv->id,
                'type' => $inv->type->value,
                'sender' => [
                    'id' => $inv->sender->id,
                    'name' => $inv->sender->name,
                    'mmii' => $inv->sender->mmii,
                ],
                'status' => $inv->status->value,
                'expires_at' => $inv->expires_at->toISOString(),
                'created_at' => $inv->created_at->toISOString(),
            ]),
        ]);
    }

    /**
     * GET /hub/{type}/sent-invitation
     * Retourne l'invitation pending envoyée par l'utilisateur
     */
    public function mySentInvitation(string $type, Request $request): JsonResponse
    {
        $hubType = HubType::from($type);
        $user = $request->user();

        $invitation = $this->hubService->getSentPendingInvitation($user, $hubType);

        if (!$invitation) {
            return response()->json(['invitation' => null]);
        }

        return response()->json([
            'invitation' => [
                'id' => $invitation->id,
                'type' => $invitation->type->value,
                'receiver' => [
                    'id' => $invitation->receiver->id,
                    'name' => $invitation->receiver->name,
                    'mmii' => $invitation->receiver->mmii,
                ],
                'status' => $invitation->status->value,
                'expires_at' => $invitation->expires_at->toISOString(),
            ],
        ]);
    }

    /**
     * POST /hub/{type}/invitations/{id}/accept
     * Accepte l'invitation, crée une room, broadcast aux deux joueurs
     */
    public function acceptInvitation(string $type, string $id, Request $request): JsonResponse
    {
        $invitation = HubInvitation::find($id);

        if (!$invitation) {
            return response()->json(['error' => 'Invitation introuvable'], 404);
        }

        // Vérifier que c'est bien le receiver
        if ($invitation->receiver_id !== $request->user()->id) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        // Vérifier que l'invitation est toujours valide
        if (!$invitation->isPending()) {
            return response()->json(['error' => 'Cette invitation n\'est plus valide'], 400);
        }

        $room = $this->hubService->acceptInvitation($invitation);

        return response()->json([
            'room' => [
                'id' => $room->id,
                'type' => $room->type->value,
                'player_one' => [
                    'id' => $room->playerOne->id,
                    'name' => $room->playerOne->name,
                ],
                'player_two' => [
                    'id' => $room->playerTwo->id,
                    'name' => $room->playerTwo->name,
                ],
            ],
        ]);
    }

    /**
     * POST /hub/{type}/invitations/{id}/decline
     * Refuse l'invitation, broadcast au sender
     */
    public function declineInvitation(string $type, string $id, Request $request): JsonResponse
    {
        $invitation = HubInvitation::find($id);

        if (!$invitation) {
            return response()->json(['error' => 'Invitation introuvable'], 404);
        }

        // Vérifier que c'est bien le receiver
        if ($invitation->receiver_id !== $request->user()->id) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        // Vérifier que l'invitation est toujours pending
        if ($invitation->status !== InvitationStatus::PENDING) {
            return response()->json(['error' => 'Cette invitation n\'est plus valide'], 400);
        }

        $this->hubService->declineInvitation($invitation);

        return response()->json(['message' => 'Invitation refusée']);
    }

    /**
     * POST /hub/{type}/invitations/{id}/cancel
     * Annule une invitation envoyée (par le sender)
     */
    public function cancelInvitation(string $type, string $id, Request $request): JsonResponse
    {
        $invitation = HubInvitation::find($id);

        if (!$invitation) {
            return response()->json(['error' => 'Invitation introuvable'], 404);
        }

        // Vérifier que c'est bien le sender
        if ($invitation->sender_id !== $request->user()->id) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        // Vérifier que l'invitation est toujours pending
        if ($invitation->status !== InvitationStatus::PENDING) {
            return response()->json(['error' => 'Cette invitation n\'est plus valide'], 400);
        }

        $this->hubService->cancelInvitation($invitation);

        return response()->json(['message' => 'Invitation annulée']);
    }

    /**
     * GET /hub/rooms/{roomId}
     * Retourne les infos d'une room active
     */
    public function getRoom(string $roomId, Request $request): JsonResponse
    {
        $room = HubRoom::with(['playerOne', 'playerOne.mmii', 'playerTwo', 'playerTwo.mmii'])
            ->find($roomId);

        if (!$room) {
            return response()->json(['error' => 'Room introuvable'], 404);
        }

        // Vérifier que l'utilisateur fait partie de la room
        if (!$room->hasPlayer($request->user())) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        return response()->json([
            'room' => [
                'id' => $room->id,
                'type' => $room->type->value,
                'status' => $room->status->value,
                'player_one' => [
                    'id' => $room->playerOne->id,
                    'name' => $room->playerOne->name,
                    'mmii' => $room->playerOne->mmii,
                ],
                'player_two' => [
                    'id' => $room->playerTwo->id,
                    'name' => $room->playerTwo->name,
                    'mmii' => $room->playerTwo->mmii,
                ],
                'created_at' => $room->created_at->toISOString(),
            ],
        ]);
    }
}
