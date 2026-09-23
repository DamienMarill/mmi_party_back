<?php

namespace Tests\Feature\Models;

use App\Enums\CardRarity;
use App\Enums\LootboxTypes;
use App\Enums\UserGroups;
use App\Models\CardInstance;
use App\Models\CardTemplate;
use App\Models\CardVersion;
use App\Models\Lootbox;
use App\Models\Mmii;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    // ========== Tests de factory states ==========

    public function test_factory_creates_user(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_mmi1_state_sets_correct_groupe(): void
    {
        $user = User::factory()->mmi1()->create();

        $this->assertEquals(UserGroups::MMI1, $user->groupe);
    }

    public function test_mmi2_state_sets_correct_groupe(): void
    {
        $user = User::factory()->mmi2()->create();

        $this->assertEquals(UserGroups::MMI2, $user->groupe);
    }

    public function test_mmi3_state_sets_correct_groupe(): void
    {
        $user = User::factory()->mmi3()->create();

        $this->assertEquals(UserGroups::MMI3, $user->groupe);
    }

    public function test_staff_state_sets_correct_groupe(): void
    {
        $user = User::factory()->staff()->create();

        $this->assertEquals(UserGroups::STAFF, $user->groupe);
    }

    public function test_unverified_state_nulls_email_verified_at(): void
    {
        $user = User::factory()->unverified()->create();

        $this->assertNull($user->email_verified_at);
    }

    // ========== Tests des relations ==========

    public function test_user_can_have_mmii(): void
    {
        $mmii = Mmii::factory()->create();
        $user = User::factory()->create(['mmii_id' => $mmii->id]);

        $this->assertInstanceOf(Mmii::class, $user->mmii);
        $this->assertEquals($mmii->id, $user->mmii->id);
    }

    public function test_user_can_have_collection(): void
    {
        $user = User::factory()->create();
        $mmii = Mmii::factory()->create();
        $template = CardTemplate::factory()->student()->withLevel(1)->withMmii($mmii)->create();
        $version = CardVersion::factory()->forTemplate($template->id)->withRarity(CardRarity::COMMON)->create();
        $lootbox = Lootbox::create([
            'user_id' => $user->id,
            'type' => LootboxTypes::QUOTIDIAN,
        ]);

        CardInstance::create([
            'card_version_id' => $version->id,
            'lootbox_id' => $lootbox->id,
            'user_id' => $user->id,
        ]);

        $this->assertCount(1, $user->collection);
    }

    // ========== Tests de routeNotificationForMail ==========

    public function test_notifications_are_sent_to_um_email(): void
    {
        $user = User::factory()->create([
            'email' => 'personal@gmail.com',
            'um_email' => 'student@etu.umontpellier.fr',
        ]);

        $this->assertEquals('student@etu.umontpellier.fr', $user->routeNotificationForMail());
    }

    // ========== Tests JWT ==========

    public function test_user_has_jwt_identifier(): void
    {
        $user = User::factory()->create();

        $this->assertEquals($user->id, $user->getJWTIdentifier());
    }

    public function test_jwt_custom_claims_returns_array(): void
    {
        $user = User::factory()->create();

        $this->assertIsArray($user->getJWTCustomClaims());
    }

    // ========== Tests des casts ==========

    public function test_groupe_is_cast_to_enum(): void
    {
        $user = User::factory()->mmi1()->create();

        $this->assertInstanceOf(UserGroups::class, $user->groupe);
    }

    public function test_email_verified_at_is_cast_to_datetime(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->email_verified_at);
    }

    // ========== Tests de mass assignment ==========

    public function test_fillable_attributes(): void
    {
        $user = User::factory()->create([
            'name' => 'Test Name',
            'email' => 'test@example.com',
            'um_email' => 'test@etu.umontpellier.fr',
        ]);

        $this->assertEquals('Test Name', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals('test@etu.umontpellier.fr', $user->um_email);
    }

    // ========== Tests de hidden attributes ==========

    public function test_password_is_hidden(): void
    {
        $user = User::factory()->create();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
    }

    public function test_remember_token_is_hidden(): void
    {
        $user = User::factory()->create();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('remember_token', $array);
    }

    public function test_group_change_syncs_owned_student_card_rarities(): void
    {
        $user = User::factory()->mmi1()->create();
        $template = CardTemplate::factory()
            ->student()
            ->withLevel(1)
            ->withBaseUser($user->id)
            ->create();

        $commonVersion = CardVersion::factory()->forTemplate($template)->common()->create();
        $rareVersion = CardVersion::factory()->forTemplate($template)->rare()->create();
        $epicVersion = CardVersion::factory()->forTemplate($template)->epic()->create();

        $user->update(['groupe' => UserGroups::MMI2]);

        $this->assertEquals(CardRarity::UNCOMMON, $commonVersion->fresh()->rarity);
        $this->assertEquals(CardRarity::UNCOMMON, $rareVersion->fresh()->rarity);
        $this->assertEquals(CardRarity::EPIC, $epicVersion->fresh()->rarity);
    }

    public function test_group_change_to_non_promo_group_does_not_change_owned_card_rarities(): void
    {
        $user = User::factory()->mmi2()->create();
        $template = CardTemplate::factory()
            ->student()
            ->withLevel(2)
            ->withBaseUser($user->id)
            ->create();

        $commonVersion = CardVersion::factory()->forTemplate($template)->common()->create();
        $rareVersion = CardVersion::factory()->forTemplate($template)->rare()->create();

        $user->update(['groupe' => UserGroups::STAFF]);

        $this->assertEquals(CardRarity::COMMON, $commonVersion->fresh()->rarity);
        $this->assertEquals(CardRarity::RARE, $rareVersion->fresh()->rarity);
    }

    public function test_group_change_to_alumni_disables_owned_student_card_lootability(): void
    {
        $user = User::factory()->mmi3()->create();
        $template = CardTemplate::factory()
            ->student()
            ->withLevel(3)
            ->withBaseUser($user->id)
            ->create(['is_lootable' => true]);

        $user->update(['groupe' => UserGroups::ALUMNI]);

        $this->assertFalse($template->fresh()->is_lootable);
        $this->assertSame(UserGroups::ALUMNI->value, $template->fresh()->owner_promo);
    }

    public function test_group_change_out_of_alumni_re_enables_owned_student_card_lootability(): void
    {
        $user = User::factory()->create(['groupe' => UserGroups::ALUMNI]);
        $template = CardTemplate::factory()
            ->student()
            ->withLevel(3)
            ->withBaseUser($user->id)
            ->create(['is_lootable' => false]);

        $user->update(['groupe' => UserGroups::MMI3]);

        $this->assertTrue($template->fresh()->is_lootable);
        $this->assertSame(UserGroups::MMI3->value, $template->fresh()->owner_promo);
    }
}
