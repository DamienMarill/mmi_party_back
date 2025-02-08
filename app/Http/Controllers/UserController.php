<?php

namespace App\Http\Controllers;

use App\Enums\LootboxTypes;
use App\Models\Lootbox;
use App\Services\LootboxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{

    private LootboxService $availabilityService;

    public function __construct(LootboxService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    public function getMe()
    {
        return auth()->user()->load('mmii');
    }

    public function getLoot()
    {

        if ($this->availabilityService->checkAvailability(auth()->user()->id)['available'] === false) {
            return response()->json([
                'error' => 'You can\'t open a lootbox right now'
            ], 400);
        }

        // tirer la lootbox
        $loots = $this->availabilityService->generateLootbox();

        // ajouter les cartes à la collection de l'utilisateur
        $lootbox = new Lootbox();
        $lootbox->type = LootboxTypes::QUOTIDIAN;
        $lootbox->user_id = auth()->user()->id;
        $lootbox->save();

        $user = auth()->user();

        foreach ($loots as $card) {
            $cardInstance = new \App\Models\CardInstance();
            $cardInstance->card_version_id = $card->id;
            $cardInstance->lootbox_id = $lootbox->id;
            $cardInstance->user_id = $user->id;
            $cardInstance->save();
        }

        return $lootbox->load(['cards', 'cards.cardVersion', 'cards.cardVersion.cardTemplate', 'cards.cardVersion.cardTemplate.mmii']);
    }

    public function checkAvailability(Request $request)
    {
        $result = $this->availabilityService->checkAvailability(auth()->user()->id);

        return response()->json([
            'available' => $result['available'],
            'nextAvailableTime' => $result['nextTime'],
            'debug' => $result['debug'],
//            'reason' => $result['reason']
        ]);
    }
}
