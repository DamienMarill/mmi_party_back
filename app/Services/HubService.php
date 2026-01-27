<?php

namespace App\Services;

use App\Enums\HubType;
use App\Enums\InvitationStatus;
use App\Enums\RoomStatus;
use App\Events\InvitationCancelled;
use App\Events\InvitationReceived;
use App\Events\InvitationResponse;
use App\Events\RoomCreated;
use App\Models\HubInvitation;
use App\Models\HubRoom;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class HubService
{
    /**
     * Durée d'expiration des invitations en secondes
     */
    private const INVITATION_EXPIRY_SECONDS = 60;

    /**
     * Cooldown entre invitations vers le même joueur en secondes
     */
    private const INVITATION_COOLDOWN_SECONDS = 10;

    /**
     * Crée une invitation avec expiration automatique
     */
    public function createInvitation(User $sender, User $receiver, HubType $type): HubInvitation
    {
        $invitation = HubInvitation::create([
            'type' => $type,
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => InvitationStatus::PENDING,
            'expires_at' => now()->addSeconds(self::INVITATION_EXPIRY_SECONDS),
        ]);

        // Marquer le cooldown
        Cache::put(
            $this->getCooldownKey($sender->id, $receiver->id, $type),
            true,
            self::INVITATION_COOLDOWN_SECONDS
        );

        // Broadcast l'événement au destinataire
        event(new InvitationReceived($invitation));

        // TODO: Ajouter la notification push ici si nécessaire
        // $this->webPushService->sendToUser($receiver, [...]);

        return $invitation;
    }

    /**
     * Accepte une invitation : change le statut, crée la room
     */
    public function acceptInvitation(HubInvitation $invitation): HubRoom
    {
        // Créer la room
        $room = HubRoom::create([
            'type' => $invitation->type,
            'player_one_id' => $invitation->sender_id,
            'player_two_id' => $invitation->receiver_id,
            'status' => RoomStatus::ACTIVE,
            'invitation_id' => $invitation->id,
        ]);

        // Mettre à jour l'invitation
        $invitation->update([
            'status' => InvitationStatus::ACCEPTED,
            'room_id' => $room->id,
        ]);

        // Broadcast aux deux joueurs
        event(new InvitationResponse($invitation, $room));
        event(new RoomCreated($room));

        return $room;
    }

    /**
     * Refuse une invitation : change le statut
     */
    public function declineInvitation(HubInvitation $invitation): void
    {
        $invitation->update(['status' => InvitationStatus::DECLINED]);

        // Broadcast au sender
        event(new InvitationResponse($invitation));
    }

    /**
     * Annule une invitation (par le sender)
     */
    public function cancelInvitation(HubInvitation $invitation): void
    {
        $invitation->update(['status' => InvitationStatus::CANCELLED]);

        // Broadcast au receiver
        event(new InvitationCancelled($invitation));
    }

    /**
     * Expire les invitations dépassées
     */
    public function expireStaleInvitations(): int
    {
        $expiredInvitations = HubInvitation::where('status', InvitationStatus::PENDING)
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expiredInvitations as $invitation) {
            $invitation->update(['status' => InvitationStatus::EXPIRED]);

            // Broadcast aux deux joueurs
            event(new InvitationResponse($invitation));
            event(new InvitationCancelled($invitation));
        }

        return $expiredInvitations->count();
    }

    /**
     * Vérifie qu'un joueur peut envoyer une invitation
     */
    public function canInvite(User $sender, User $receiver, HubType $type): array
    {
        // Pas d'auto-invitation
        if ($sender->id === $receiver->id) {
            return ['can' => false, 'reason' => 'Vous ne pouvez pas vous inviter vous-même'];
        }

        // Vérifier que le sender n'a pas d'invitation pending en cours
        $pendingInvitation = HubInvitation::where('sender_id', $sender->id)
            ->where('type', $type)
            ->where('status', InvitationStatus::PENDING)
            ->where('expires_at', '>', now())
            ->exists();

        if ($pendingInvitation) {
            return ['can' => false, 'reason' => 'Vous avez déjà une invitation en attente'];
        }

        // Vérifier le cooldown
        if (Cache::has($this->getCooldownKey($sender->id, $receiver->id, $type))) {
            return ['can' => false, 'reason' => 'Veuillez patienter avant de réinviter ce joueur'];
        }

        // Vérifier que le receiver n'est pas en room active
        $receiverInRoom = HubRoom::where('type', $type)
            ->where('status', RoomStatus::ACTIVE)
            ->where(function ($query) use ($receiver) {
                $query->where('player_one_id', $receiver->id)
                    ->orWhere('player_two_id', $receiver->id);
            })
            ->exists();

        if ($receiverInRoom) {
            return ['can' => false, 'reason' => 'Ce joueur est déjà en combat'];
        }

        // Vérifier que le sender n'est pas en room active
        $senderInRoom = HubRoom::where('type', $type)
            ->where('status', RoomStatus::ACTIVE)
            ->where(function ($query) use ($sender) {
                $query->where('player_one_id', $sender->id)
                    ->orWhere('player_two_id', $sender->id);
            })
            ->exists();

        if ($senderInRoom) {
            return ['can' => false, 'reason' => 'Vous êtes déjà en combat'];
        }

        return ['can' => true, 'reason' => null];
    }

    /**
     * Récupère les invitations pending reçues par un utilisateur
     */
    public function getPendingInvitations(User $user, HubType $type): \Illuminate\Database\Eloquent\Collection
    {
        return HubInvitation::with(['sender', 'sender.mmii'])
            ->where('receiver_id', $user->id)
            ->where('type', $type)
            ->where('status', InvitationStatus::PENDING)
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Récupère l'invitation pending envoyée par un utilisateur
     */
    public function getSentPendingInvitation(User $user, HubType $type): ?HubInvitation
    {
        return HubInvitation::with(['receiver', 'receiver.mmii'])
            ->where('sender_id', $user->id)
            ->where('type', $type)
            ->where('status', InvitationStatus::PENDING)
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Génère la clé de cache pour le cooldown
     */
    private function getCooldownKey(string $senderId, string $receiverId, HubType $type): string
    {
        return "hub_cooldown:{$type->value}:{$senderId}:{$receiverId}";
    }
}
