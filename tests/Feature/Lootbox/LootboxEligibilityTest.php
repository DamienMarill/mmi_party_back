<?php

namespace Tests\Feature\Lootbox;

use App\Enums\CardRarity;
use App\Enums\CardTypes;
use App\Models\CardTemplate;
use App\Models\CardVersion;
use App\Services\LootboxService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class LootboxEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_loot_ignores_non_lootable_templates(): void
    {
        Config::set('app.loot_rate', [
            ['common' => 1],
        ]);

        $lootableTemplate = CardTemplate::factory()->create([
            'type' => CardTypes::STUDENT,
            'level' => 1,
            'is_lootable' => true,
        ]);
        $lootableVersion = CardVersion::factory()->create([
            'card_template_id' => $lootableTemplate->id,
            'rarity' => CardRarity::COMMON,
        ]);

        $nonLootableTemplate = CardTemplate::factory()->create([
            'type' => CardTypes::STUDENT,
            'level' => 1,
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

    public function test_generate_loot_throws_when_no_lootable_card_exists_for_rarity(): void
    {
        Config::set('app.loot_rate', [
            ['common' => 1],
        ]);

        $nonLootableTemplate = CardTemplate::factory()->create([
            'type' => CardTypes::STUDENT,
            'level' => 1,
            'is_lootable' => false,
        ]);
        CardVersion::factory()->create([
            'card_template_id' => $nonLootableTemplate->id,
            'rarity' => CardRarity::COMMON,
        ]);

        $service = new LootboxService();

        $this->expectException(ModelNotFoundException::class);
        $service->generateLoot(0);
    }

    public function test_generate_loot_for_uncommon_student_targets_level_two(): void
    {
        Config::set('app.loot_rate', [
            ['uncommon' => 1],
        ]);

        $levelTwoTemplate = CardTemplate::factory()->create([
            'type' => CardTypes::STUDENT,
            'level' => 2,
            'is_lootable' => true,
        ]);
        $levelTwoVersion = CardVersion::factory()->create([
            'card_template_id' => $levelTwoTemplate->id,
            'rarity' => CardRarity::UNCOMMON,
        ]);

        $levelOneTemplate = CardTemplate::factory()->create([
            'type' => CardTypes::STUDENT,
            'level' => 1,
            'is_lootable' => true,
        ]);
        CardVersion::factory()->create([
            'card_template_id' => $levelOneTemplate->id,
            'rarity' => CardRarity::UNCOMMON,
        ]);

        $loot = (new LootboxService())->generateLoot(0);
        $this->assertTrue($loot->is($levelTwoVersion));
    }

    public function test_generate_loot_for_rare_student_targets_level_three(): void
    {
        Config::set('app.loot_rate', [
            ['rare' => 1],
        ]);

        $levelThreeTemplate = CardTemplate::factory()->create([
            'type' => CardTypes::STUDENT,
            'level' => 3,
            'is_lootable' => true,
        ]);
        $levelThreeVersion = CardVersion::factory()->create([
            'card_template_id' => $levelThreeTemplate->id,
            'rarity' => CardRarity::RARE,
        ]);

        CardVersion::factory()->create([
            'card_template_id' => CardTemplate::factory()->create([
                'type' => CardTypes::STUDENT,
                'level' => 2,
                'is_lootable' => true,
            ])->id,
            'rarity' => CardRarity::RARE,
        ]);

        $loot = (new LootboxService())->generateLoot(0);
        $this->assertTrue($loot->is($levelThreeVersion));
    }

    public function test_generate_loot_allows_student_cards_for_epic_rarity(): void
    {
        Config::set('app.loot_rate', [
            ['epic' => 1],
        ]);

        $epicStudentTemplate = CardTemplate::factory()->create([
            'type' => CardTypes::STUDENT,
            'level' => 2,
            'is_lootable' => true,
        ]);
        $epicStudentVersion = CardVersion::factory()->create([
            'card_template_id' => $epicStudentTemplate->id,
            'rarity' => CardRarity::EPIC,
        ]);

        $loot = (new LootboxService())->generateLoot(0);
        $this->assertTrue($loot->is($epicStudentVersion));
    }
}
