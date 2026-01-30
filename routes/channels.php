<?php

use App\Models\HubRoom;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Canal privé utilisateur — seul le propriétaire peut écouter
Broadcast::channel('user.{userId}', function (User $user, string $userId) {
    return $user->id === $userId;
});

// Canal de présence hub — tout utilisateur authentifié peut rejoindre
Broadcast::channel('hub.{type}', function (User $user, string $type) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'mmii' => $user->mmii,
    ];
});

// Canal privé room — seuls les deux joueurs de la room
Broadcast::channel('hub-room.{roomId}', function (User $user, string $roomId) {
    $room = HubRoom::find($roomId);
    return $room && ($user->id === $room->player_one_id || $user->id === $room->player_two_id);
});
