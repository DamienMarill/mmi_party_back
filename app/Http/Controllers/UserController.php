<?php

namespace App\Http\Controllers;

use App\Enums\CardRarity;
use App\Enums\CardTypes;
use App\Enums\LootboxTypes;
use App\Models\Lootbox;
use App\Models\Mmii;
use App\Models\CardTemplate;
use App\Models\CardVersion;
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

    /**
     * Finalize profile for Moodle OAuth users.
     * Creates MMII and CardTemplate if not already present.
     */
    public function finalizeProfile(Request $request)
    {
        $validated = $request->validate([
            'mmiiData' => 'required|array',
            'background' => 'sometimes|string',
            'skills' => 'sometimes|array',
        ]);

        $user = auth()->user();

        // Check if user already has MMII
        if ($user->mmii_id) {
            return response()->json(['error' => 'Profile already finalized'], 400);
        }

        // Create MMII
        $mmii = new Mmii();
        $mmii->background = $validated['background'] ?? 'default';
        $mmii->shape = $validated['mmiiData'];
        $mmii->save();

        // Link MMII to user
        $user->mmii_id = $mmii->id;
        $user->save();

        // Find and modify existing CardTemplate for user
        $skills = $validated['skills'] ?? null;
        if ($skills) {
            // Determine level from user's groupe (mmi1, mmi2, mmi3)
            $level = match ($user->groupe?->value ?? 'mmi1') {
                'mmi1' => 1,
                'mmi2' => 2,
                'mmi3' => 3,
                default => 1,
            };

            // Find an unassigned template with same level (base_user = null means not yet assigned to a real student)
            $template = CardTemplate::where('type', CardTypes::STUDENT)
                ->where('level', $level)
                ->whereNull('base_user')
                ->get()
                ->sortBy(function ($t) use ($skills) {
                    // Calculate distance between template stats and user's chosen skills
                    $templateStats = $t->stats ?? [];
                    $distance = 0;
                    foreach ($skills as $key => $value) {
                        $distance += pow(($templateStats[$key] ?? 0) - $value, 2);
                    }
                    return $distance;
                })
                ->first();

            if ($template) {
                // Modify the existing template with user's data
                $template->mmii_id = $mmii->id;
                $template->name = $user->firstName . ' ' . $user->lastName;
                $template->stats = $skills;
                $template->base_user = $user->id;
                $template->save();

                Log::info('Assigned existing CardTemplate to user', [
                    'user_id' => $user->id,
                    'template_id' => $template->id
                ]);
            } else {
                // Fallback: Create new template + card_version if none available
                Log::warning('No unassigned CardTemplate found for level ' . $level . ', creating new one', [
                    'user_id' => $user->id
                ]);

                // Determine rarity based on level: MMI1=common, MMI2=uncommon, MMI3=rare
                $rarity = match ($level) {
                    1 => CardRarity::COMMON,
                    2 => CardRarity::UNCOMMON,
                    3 => CardRarity::RARE,
                    default => CardRarity::COMMON,
                };

                // Create new template
                $template = new CardTemplate();
                $template->mmii_id = $mmii->id;
                $template->name = $user->firstName . ' ' . $user->lastName;
                $template->type = CardTypes::STUDENT;
                $template->level = $level;
                $template->stats = $skills;
                $template->base_user = $user->id;
                $template->save();

                // Create card_version for this template
                $version = new CardVersion();
                $version->card_template_id = $template->id;
                $version->rarity = $rarity;
                $version->save();

                Log::info('Created new CardTemplate and CardVersion for user', [
                    'user_id' => $user->id,
                    'template_id' => $template->id,
                    'version_id' => $version->id,
                    'rarity' => $rarity->value
                ]);
            }
        }

        Log::info('Profile finalized for Moodle user', ['user_id' => $user->id]);

        return response()->json([
            'message' => 'Profile finalized successfully',
            'user' => $user->load('mmii'),
        ]);
    }
}
