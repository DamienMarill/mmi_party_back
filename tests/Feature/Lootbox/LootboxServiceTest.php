<?php

namespace Tests\Feature\Lootbox;

use App\Enums\LootboxTypes;
use App\Models\Lootbox;
use App\Models\User;
use App\Services\LootboxService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LootboxServiceTest extends TestCase
{
    use RefreshDatabase;

    private LootboxService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LootboxService();
        $this->user = User::factory()->mmi1()->create();
    }

    // ========== Tests de checkAvailability ==========

    public function test_availability_returns_correct_structure(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(13, 0));

        $result = $this->service->checkAvailability($this->user->id);

        $this->assertArrayHasKey('available', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('nextTime', $result);
    }

    public function test_availability_returns_true_when_no_recent_lootbox(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(13, 0)); // Apres 12:35

        $result = $this->service->checkAvailability($this->user->id);

        $this->assertTrue($result['available']);
    }

    public function test_availability_returns_false_after_opening_all_slots(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(19, 0)); // Apres les deux creneaux

        // Creer 2 lootboxes (tous les creneaux utilises)
        Lootbox::create([
            'user_id' => $this->user->id,
            'type' => LootboxTypes::QUOTIDIAN,
            'created_at' => Carbon::today()->setTime(12, 40),
        ]);

        Lootbox::create([
            'user_id' => $this->user->id,
            'type' => LootboxTypes::QUOTIDIAN,
            'created_at' => Carbon::today()->setTime(18, 40),
        ]);

        $result = $this->service->checkAvailability($this->user->id);

        $this->assertFalse($result['available']);
        $this->assertEquals(0, $result['count']);
    }

    public function test_availability_counts_remaining_slots(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(19, 0)); // Apres les deux creneaux

        // Une seule lootbox ouverte
        Lootbox::create([
            'user_id' => $this->user->id,
            'type' => LootboxTypes::QUOTIDIAN,
            'created_at' => Carbon::today()->setTime(12, 40),
        ]);

        $result = $this->service->checkAvailability($this->user->id);

        $this->assertTrue($result['available']);
        $this->assertEquals(1, $result['count']);
    }

    public function test_availability_ignores_old_lootboxes(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(13, 0));

        // Lootbox d'il y a 2 jours (hors periode)
        Lootbox::create([
            'user_id' => $this->user->id,
            'type' => LootboxTypes::QUOTIDIAN,
            'created_at' => Carbon::today()->subDays(2),
        ]);

        $result = $this->service->checkAvailability($this->user->id);

        $this->assertTrue($result['available']);
    }

    public function test_next_time_returns_first_slot_before_it(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(10, 0)); // Avant 12:35

        $result = $this->service->checkAvailability($this->user->id);

        $this->assertEquals('12:35', $result['nextTime']);
    }

    public function test_next_time_returns_second_slot_between_slots(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(15, 0)); // Entre 12:35 et 18:35

        $result = $this->service->checkAvailability($this->user->id);

        $this->assertEquals('18:35', $result['nextTime']);
    }

    public function test_next_time_wraps_to_next_day(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(20, 0)); // Apres 18:35

        $result = $this->service->checkAvailability($this->user->id);

        $this->assertEquals('12:35', $result['nextTime']);
    }

    public function test_different_users_have_independent_availability(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(19, 0));

        $otherUser = User::factory()->mmi1()->create();

        // Premier utilisateur a ouvert une lootbox
        Lootbox::create([
            'user_id' => $this->user->id,
            'type' => LootboxTypes::QUOTIDIAN,
            'created_at' => Carbon::today()->setTime(12, 40),
        ]);

        $resultUser1 = $this->service->checkAvailability($this->user->id);
        $resultUser2 = $this->service->checkAvailability($otherUser->id);

        $this->assertEquals(1, $resultUser1['count']);
        $this->assertEquals(2, $resultUser2['count']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset time
        parent::tearDown();
    }
}
