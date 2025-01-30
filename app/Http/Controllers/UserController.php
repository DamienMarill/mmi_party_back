<?php

namespace App\Http\Controllers;

use App\Enums\LootboxTypes;
use App\Models\Lootbox;
use App\Services\LootboxService;
use Illuminate\Http\Request;

class UserController extends Controller
{

    public function getMe()
    {
        return auth()->user();
    }

    public function getLoot()
    {
        $lootboxService = new LootboxService();
        // vérifier si l'utilisateur a une lootbox
        $nextLootbox = $lootboxService->getNextLootbox(auth()->user());
        if (!$nextLootbox['status']['available']) {
            return response()->json([
                'message' => $nextLootbox['message'].' '.$nextLootbox['remaining_time'],
                'detail' => $nextLootbox
            ], 400);
        }

        // tirer la lootbox
        $loots = $lootboxService->generateLootbox();

        // ajouter les cartes à la collection de l'utilisateur
        $lootbox = new Lootbox();
        $lootbox->type = LootboxTypes::QUOTIDIAN;
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
}
