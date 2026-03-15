<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TradeStateUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $roomId;
    public array $tradeState;

    /**
     * Create a new event instance.
     */
    public function __construct(string $roomId, array $tradeState)
    {
        $this->roomId = $roomId;
        $this->tradeState = $tradeState;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('hub-room.' . $this->roomId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'trade.state.updated';
    }
}
