<?php

namespace Tests\Feature\Models;

use App\Enums\CardRarity;
use App\Enums\LootboxTypes;
use App\Models\CardInstance;
use App\Models\CardTemplate;
use App\Models\CardVersion;
use App\Models\Lootbox;
use App\Models\Mmii;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardVersionTest extends TestCase
{
    use RefreshDatabase;

    private CardTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        $mmii = Mmii::factory()->create();
        $this->template = CardTemplate::factory()->student()->withLevel(1)->withMmii($mmii)->create();
    }

    // ========== Tests de factory ==========

    public function test_factory_creates_card_version(): void
    {
        $version = CardVersion::factory()->forTemplate($this->template->id)->withRarity(CardRarity::COMMON)->create();

        $this->assertDatabaseHas('card_versions', ['id' => $version->id]);
    }

    public function test_rarity_states(): void
    {
        $common = CardVersion::factory()->forTemplate($this->template->id)->withRarity(CardRarity::COMMON)->create();
        $rare = CardVersion::factory()->forTemplate($this->template->id)->withRarity(CardRarity::RARE)->create();
        $epic = CardVersion::factory()->forTemplate($this->template->id)->withRarity(CardRarity::EPIC)->create();

        $this->assertEquals(CardRarity::COMMON, $common->rarity);
        $this->assertEquals(CardRarity::RARE, $rare->rarity);
        $this->assertEquals(CardRarity::EPIC, $epic->rarity);
    }

    // ========== Tests des relations ==========

    public function test_version_belongs_to_template(): void
    {
        $version = CardVersion::factory()->forTemplate($this->template->id)->withRarity(CardRarity::COMMON)->create();

        $this->assertInstanceOf(CardTemplate::class, $version->cardTemplate);
        $this->assertEquals($this->template->id, $version->cardTemplate->id);
    }

    public function test_card_instances_reference_version(): void
    {
        $version = CardVersion::factory()->forTemplate($this->template->id)->withRarity(CardRarity::COMMON)->create();
        $user = User::factory()->create();
        $lootbox = Lootbox::create([
            'user_id' => $user->id,
            'type' => LootboxTypes::QUOTIDIAN,
        ]);

        $instance = CardInstance::create([
            'card_version_id' => $version->id,
            'lootbox_id' => $lootbox->id,
            'user_id' => $user->id,
        ]);

        // Verifie que l'instance reference bien la version
        $this->assertEquals($version->id, $instance->card_version_id);

        // Verifie qu'on peut compter les instances via la base de donnees
        $instanceCount = CardInstance::where('card_version_id', $version->id)->count();
        $this->assertEquals(1, $instanceCount);
    }

    // ========== Tests des casts ==========

    public function test_rarity_is_cast_to_enum(): void
    {
        $version = CardVersion::factory()->forTemplate($this->template->id)->withRarity(CardRarity::RARE)->create();

        $this->assertInstanceOf(CardRarity::class, $version->rarity);
    }

    // ========== Tests d'image ==========

    public function test_version_image_is_nullable(): void
    {
        $version = CardVersion::factory()->forTemplate($this->template->id)->withRarity(CardRarity::COMMON)->create();

        // L'image est null par defaut dans la factory
        $this->assertNull($version->image);
    }

    public function test_version_can_have_image(): void
    {
        $version = CardVersion::factory()
            ->forTemplate($this->template->id)
            ->withRarity(CardRarity::COMMON)
            ->withImage('cards/test.png')
            ->create();

        $this->assertNotNull($version->image);
        $this->assertEquals('cards/test.png', $version->image);
    }
}
