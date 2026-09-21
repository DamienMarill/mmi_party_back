<?php

namespace Tests\Feature\Lootbox;

use App\Enums\CardRarity;
use App\Enums\CardTypes;
use App\Models\CardTemplate;
use App\Models\CardVersion;
use App\Services\LootboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LootboxEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_loot_ignores_non_lootable_templates(): void
    {
        $lootableTemplate = CardTemplate::factory()->create([
            'type' => CardTypes::STUDENT,
            'is_lootable' => true,
        ]);
        $lootableVersion = CardVersion::factory()->create([
            'card_template_id' => $lootableTemplate->id,
            'rarity' => CardRarity::COMMON,
        ]);

        $nonLootableTemplate = CardTemplate::factory()->create([
            'type' => CardTypes::STUDENT,
            'is_lootable' => false,
        ]);
        CardVersion::factory()->create([
            'card_template_id' => $nonLootableTemplate->id,
            'rarity' => CardRarity::COMMON,
        ]);

        $service = new LootboxService();
        $loot = $service->generateLoot(0);

        $this->assertTrue($loot->is($lootableVersion));
    }
}
