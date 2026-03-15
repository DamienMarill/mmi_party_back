<?php

namespace App\Console\Commands;

use App\Enums\RoomStatus;
use App\Events\TradeCancelled;
use App\Models\HubRoom;
use Illuminate\Console\Command;

class CloseStaleRoomsCommand extends Command
{
    protected $signature = 'rooms:close-stale {--minutes=30 : Durée maximale en minutes}';
    protected $description = 'Ferme les rooms actives ouvertes depuis trop longtemps';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');

        $staleRooms = HubRoom::where('status', RoomStatus::ACTIVE)
            ->where('created_at', '<', now()->subMinutes($minutes))
            ->get();

        if ($staleRooms->isEmpty()) {
            $this->info('Aucune room périmée.');
            return self::SUCCESS;
        }

        foreach ($staleRooms as $room) {
            $room->status = RoomStatus::ABANDONED;
            $room->save();

            broadcast(new TradeCancelled($room->id));

            $this->line("Room {$room->id} fermée (créée le {$room->created_at})");
        }

        $this->info("{$staleRooms->count()} room(s) fermée(s).");

        return self::SUCCESS;
    }
}
