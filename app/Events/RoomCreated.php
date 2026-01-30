<?php

namespace App\Events;

use App\Models\HubRoom;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public HubRoom $room
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->room->player_one_id),
            new PrivateChannel('user.' . $this->room->player_two_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'room.created';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'room' => [
                'id' => $this->room->id,
                'type' => $this->room->type->value,
                'players' => [
                    [
                        'id' => $this->room->playerOne->id,
                        'name' => $this->room->playerOne->name,
                        'mmii' => $this->room->playerOne->mmii,
                    ],
                    [
                        'id' => $this->room->playerTwo->id,
                        'name' => $this->room->playerTwo->name,
                        'mmii' => $this->room->playerTwo->mmii,
                    ],
                ],
            ],
        ];
    }
}
