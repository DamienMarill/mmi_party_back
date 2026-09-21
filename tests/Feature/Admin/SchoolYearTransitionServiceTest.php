<?php

namespace Tests\Feature\Admin;

use App\Enums\CardRarity;
use App\Enums\CardTypes;
use App\Enums\UserGroups;
use App\Models\CardTemplate;
use App\Models\CardVersion;
use App\Models\User;
use App\Services\SchoolYearTransitionService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolYearTransitionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_transitions_groups_and_rebalances_bots(): void
    {
        $service = app(SchoolYearTransitionService::class);
        $admin = User::factory()->create(['is_admin' => true]);

        $mmi1Real = User::factory()->create(['groupe' => UserGroups::MMI1, 'moodle_id' => 101]);
        $mmi2Real = User::factory()->create(['groupe' => UserGroups::MMI2, 'moodle_id' => 102]);
        $mmi3Real = User::factory()->create(['groupe' => UserGroups::MMI3, 'moodle_id' => 103]);

        $mmi1Template = CardTemplate::factory()->create([
            'type' => CardTypes::STUDENT,
            'level' => 1,
            'base_user' => $mmi1Real->id,
            'is_lootable' => true,
        ]);
        CardVersion::factory()->create([
            'card_template_id' => $mmi1Template->id,
            'rarity' => CardRarity::COMMON,
        ]);

        $mmi2Template = CardTemplate::factory()->create([
            'type' => CardTypes::STUDENT,
            'level' => 2,
            'base_user' => $mmi2Real->id,
            'is_lootable' => true,
        ]);
        CardVersion::factory()->create([
            'card_template_id' => $mmi2Template->id,
            'rarity' => CardRarity::UNCOMMON,
        ]);

        $mmi3Template = CardTemplate::factory()->create([
            'type' => CardTypes::STUDENT,
            'level' => 3,
            'base_user' => $mmi3Real->id,
            'is_lootable' => true,
        ]);
        CardVersion::factory()->create([
            'card_template_id' => $mmi3Template->id,
            'rarity' => CardRarity::RARE,
        ]);

        User::factory()->count(2)->create(['groupe' => UserGroups::MMI1, 'moodle_id' => null, 'is_admin' => false]);
        User::factory()->count(1)->create(['groupe' => UserGroups::MMI2, 'moodle_id' => null, 'is_admin' => false]);
        User::factory()->count(3)->create(['groupe' => UserGroups::MMI3, 'moodle_id' => null, 'is_admin' => false]);
        User::factory()->count(4)->create(['groupe' => UserGroups::ALUMNI, 'moodle_id' => null, 'is_admin' => false]);

        $service->execute('2026-2027', ['mmi1' => 5, 'mmi2' => 3, 'mmi3' => 1], $admin);

        $this->assertEquals(UserGroups::MMI2, $mmi1Real->refresh()->groupe);
        $this->assertEquals(UserGroups::MMI3, $mmi2Real->refresh()->groupe);
        $this->assertEquals(UserGroups::ALUMNI, $mmi3Real->refresh()->groupe);

        $this->assertDatabaseHas('card_templates', [
            'id' => $mmi1Template->id,
            'level' => 2,
            'is_lootable' => true,
        ]);
        $this->assertDatabaseHas('card_templates', [
            'id' => $mmi2Template->id,
            'level' => 3,
            'is_lootable' => true,
        ]);
        $this->assertDatabaseHas('card_templates', [
            'id' => $mmi3Template->id,
            'level' => 3,
            'is_lootable' => false,
        ]);

        $this->assertDatabaseHas('card_versions', [
            'card_template_id' => $mmi1Template->id,
            'rarity' => CardRarity::UNCOMMON->value,
        ]);
        $this->assertDatabaseHas('card_versions', [
            'card_template_id' => $mmi2Template->id,
            'rarity' => CardRarity::RARE->value,
        ]);
        $this->assertDatabaseHas('card_versions', [
            'card_template_id' => $mmi3Template->id,
            'rarity' => CardRarity::RARE->value,
        ]);

        $this->assertSame(5, $this->botCount(UserGroups::MMI1));
        $this->assertSame(2, $this->botCount(UserGroups::MMI2));
        $this->assertSame(0, $this->botCount(UserGroups::MMI3));
        $this->assertSame(0, $this->botCount(UserGroups::ALUMNI));

        $this->assertSame(5, User::query()->where('groupe', UserGroups::MMI1)->count());
        $this->assertSame(3, User::query()->where('groupe', UserGroups::MMI2)->count());
        $this->assertSame(1, User::query()->where('groupe', UserGroups::MMI3)->count());

        $this->assertDatabaseHas('school_year_transitions', [
            'school_year' => '2026-2027',
            'executed_by' => $admin->id,
        ]);
    }

    public function test_it_cannot_run_twice_for_the_same_school_year(): void
    {
        $service = app(SchoolYearTransitionService::class);

        $service->execute('2026-2027', ['mmi1' => 1, 'mmi2' => 1, 'mmi3' => 1]);

        $this->expectException(DomainException::class);
        $service->execute('2026-2027', ['mmi1' => 1, 'mmi2' => 1, 'mmi3' => 1]);
    }

    private function botCount(UserGroups $group): int
    {
        return User::query()
            ->where('groupe', $group)
            ->whereNull('moodle_id')
            ->where('is_admin', false)
            ->count();
    }
}
