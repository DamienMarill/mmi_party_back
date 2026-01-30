<?php

namespace Tests\Feature\Models;

use App\Enums\CardRarity;
use App\Enums\CardTypes;
use App\Models\CardTemplate;
use App\Models\CardVersion;
use App\Models\Mmii;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardTemplateTest extends TestCase
{
    use RefreshDatabase;

    // ========== Tests de factory ==========

    public function test_factory_creates_card_template(): void
    {
        $template = CardTemplate::factory()->create();

        $this->assertDatabaseHas('card_templates', ['id' => $template->id]);
    }

    public function test_student_state_creates_student_card(): void
    {
        $mmii = Mmii::factory()->create();
        $template = CardTemplate::factory()->student()->withLevel(1)->withMmii($mmii)->create();

        $this->assertEquals(CardTypes::STUDENT, $template->type);
    }

    public function test_staff_state_creates_staff_card(): void
    {
        $mmii = Mmii::factory()->create();
        $template = CardTemplate::factory()->staff()->withMmii($mmii)->create();

        $this->assertEquals(CardTypes::STAFF, $template->type);
    }

    public function test_object_state_creates_object_card(): void
    {
        $template = CardTemplate::factory()->object()->create();

        $this->assertEquals(CardTypes::OBJECT, $template->type);
    }

    // ========== Tests de niveau ==========

    public function test_student_card_has_level(): void
    {
        $mmii = Mmii::factory()->create();
        $template = CardTemplate::factory()->student()->withLevel(2)->withMmii($mmii)->create();

        $this->assertEquals(2, $template->level);
    }

    public function test_staff_card_has_null_level(): void
    {
        $mmii = Mmii::factory()->create();
        $template = CardTemplate::factory()->staff()->withMmii($mmii)->create();

        $this->assertNull($template->level);
    }

    public function test_object_card_has_null_level(): void
    {
        $template = CardTemplate::factory()->object()->create();

        $this->assertNull($template->level);
    }

    // ========== Tests de stats et shape ==========

    public function test_student_card_has_stats(): void
    {
        $mmii = Mmii::factory()->create();
        $template = CardTemplate::factory()->student()->withLevel(1)->withMmii($mmii)->create();

        $this->assertIsArray($template->stats);
        $this->assertNotEmpty($template->stats);
    }

    public function test_student_card_has_shape(): void
    {
        $mmii = Mmii::factory()->create();
        $template = CardTemplate::factory()->student()->withLevel(1)->withMmii($mmii)->create();

        $this->assertIsArray($template->shape);
        $this->assertNotEmpty($template->shape);
    }

    public function test_staff_card_has_null_stats(): void
    {
        $mmii = Mmii::factory()->create();
        $template = CardTemplate::factory()->staff()->withMmii($mmii)->create();

        $this->assertNull($template->stats);
    }

    // ========== Tests des relations ==========

    public function test_template_belongs_to_mmii(): void
    {
        $mmii = Mmii::factory()->create();
        $template = CardTemplate::factory()->student()->withLevel(1)->withMmii($mmii)->create();

        $this->assertInstanceOf(Mmii::class, $template->mmii);
        $this->assertEquals($mmii->id, $template->mmii->id);
    }

    public function test_template_belongs_to_base_user(): void
    {
        $user = User::factory()->create();
        $mmii = Mmii::factory()->create();
        $template = CardTemplate::factory()
            ->student()
            ->withLevel(1)
            ->withMmii($mmii)
            ->withBaseUser($user->id)
            ->create();

        $this->assertInstanceOf(User::class, $template->baseUser);
        $this->assertEquals($user->id, $template->baseUser->id);
    }

    public function test_template_has_many_versions(): void
    {
        $mmii = Mmii::factory()->create();
        $template = CardTemplate::factory()->student()->withLevel(1)->withMmii($mmii)->create();

        CardVersion::factory()->forTemplate($template->id)->withRarity(CardRarity::COMMON)->create();
        CardVersion::factory()->forTemplate($template->id)->withRarity(CardRarity::RARE)->create();

        $this->assertCount(2, $template->cardVersions);
    }

    // ========== Tests des casts ==========

    public function test_type_is_cast_to_enum(): void
    {
        $template = CardTemplate::factory()->object()->create();

        $this->assertInstanceOf(CardTypes::class, $template->type);
    }

    public function test_stats_is_cast_to_array(): void
    {
        $mmii = Mmii::factory()->create();
        $template = CardTemplate::factory()->student()->withLevel(1)->withMmii($mmii)->create();

        $this->assertIsArray($template->stats);
    }

    public function test_shape_is_cast_to_array(): void
    {
        $mmii = Mmii::factory()->create();
        $template = CardTemplate::factory()->student()->withLevel(1)->withMmii($mmii)->create();

        $this->assertIsArray($template->shape);
    }
}
