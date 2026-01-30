<?php

namespace App\Events;

use App\Models\HubInvitation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvitationReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public HubInvitation $invitation
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->invitation->receiver_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'invitation.received';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'invitation' => [
                'id' => $this->invitation->id,
                'type' => $this->invitation->type->value,
                'sender' => [
                    'id' => $this->invitation->sender->id,
                    'name' => $this->invitation->sender->name,
                    'mmii' => $this->invitation->sender->mmii,
                ],
                'expires_at' => $this->invitation->expires_at->toISOString(),
                'created_at' => $this->invitation->created_at->toISOString(),
            ],
        ];
    }
}
