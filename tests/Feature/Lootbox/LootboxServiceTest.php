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

    /**
     * Helper pour créer une lootbox avec une date spécifique
     * Calcule automatiquement le slot_used_at en fonction des horaires de slots
     */
    private function createLootboxAt(Carbon $date, ?Carbon $slotUsedAt = null): Lootbox
    {
        $lootbox = new Lootbox();
        $lootbox->user_id = $this->user->id;
        $lootbox->type = LootboxTypes::QUOTIDIAN;
        $lootbox->timestamps = false; // Désactiver les timestamps automatiques
        $lootbox->created_at = $date;
        $lootbox->updated_at = $date;

        // Si slot_used_at n'est pas spécifié, calculer le slot le plus proche avant created_at
        if ($slotUsedAt === null) {
            $slotTimes = config('app.lootbox_times', ['12:35', '18:35']);
            $nearestSlot = null;
            $minDiff = PHP_INT_MAX;

            foreach ($slotTimes as $slotTime) {
                $slotToday = $date->copy()->setTimeFromTimeString($slotTime);
                if ($slotToday <= $date) {
                    $diff = $date->diffInMinutes($slotToday);
                    if ($diff < $minDiff) {
                        $minDiff = $diff;
                        $nearestSlot = $slotToday;
                    }
                }
            }

            // Si aucun slot aujourd'hui n'est avant created_at, prendre le dernier slot d'hier
            if ($nearestSlot === null) {
                $lastSlotTime = end($slotTimes);
                $nearestSlot = $date->copy()->subDay()->setTimeFromTimeString($lastSlotTime);
            }

            $lootbox->slot_used_at = $nearestSlot;
        } else {
            $lootbox->slot_used_at = $slotUsedAt;
        }

        $lootbox->save();

        return $lootbox;
    }

    // ========== Tests de structure ==========

    public function test_availability_returns_correct_structure(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(13, 0));

        $result = $this->service->checkAvailability($this->user->id);

        $this->assertArrayHasKey('available', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('nextTime', $result);
        $this->assertArrayHasKey('debug', $result);
    }

    // ========== Tests de disponibilité basique ==========

    public function test_availability_returns_two_when_no_lootbox_opened(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(10, 0));

        $result = $this->service->checkAvailability($this->user->id);

        $this->assertTrue($result['available']);
        $this->assertEquals(2, $result['count']);
    }

    public function test_availability_returns_two_after_both_slots_today(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(19, 0));

        $result = $this->service->checkAvailability($this->user->id);

        $this->assertTrue($result['available']);
        $this->assertEquals(2, $result['count']);
    }

    public function test_availability_returns_two_between_slots(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(15, 0));

        $result = $this->service->checkAvailability($this->user->id);

        $this->assertTrue($result['available']);
        $this->assertEquals(2, $result['count']);
    }

    // ========== Tests après ouverture de lootbox ==========

    public function test_availability_returns_one_after_opening_one_lootbox(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(19, 0));

        // Une seule lootbox ouverte après le slot 12:35
        $this->createLootboxAt(Carbon::today()->setTime(12, 40));

        $result = $this->service->checkAvailability($this->user->id);

        $this->assertTrue($result['available']);
        $this->assertEquals(1, $result['count']);
    }

    public function test_availability_returns_zero_after_opening_two_lootboxes(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(19, 0));

        // Deux lootboxes ouvertes
        $this->createLootboxAt(Carbon::today()->setTime(12, 40));
        $this->createLootboxAt(Carbon::today()->setTime(18, 40));

        $result = $this->service->checkAvailability($this->user->id);

        $this->assertFalse($result['available']);
        $this->assertEquals(0, $result['count']);
    }

    // ========== Tests d'expiration et régénération ==========

    public function test_slot_regenerates_after_24h(): void
    {
        // Jour J à 13h00
        Carbon::setTestNow(Carbon::today()->setTime(13, 0));

        // L'utilisateur a ouvert une lootbox HIER à 12:40
        // À 13h, les slots disponibles sont:
        // - 12:35 aujourd'hui 
        // - 18:35 hier
        // Le plus ancien est 18:35 hier
        // La lootbox de hier 12:40 est AVANT 18:35 hier, donc ne compte pas
        $this->createLootboxAt(Carbon::yesterday()->setTime(12, 40));

        $result = $this->service->checkAvailability($this->user->id);

        $this->assertTrue($result['available']);
        $this->assertEquals(2, $result['count']);
    }

    public function test_old_lootboxes_dont_block_new_slots(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(13, 0));

        // Lootbox d'il y a 2 jours
        $this->createLootboxAt(Carbon::today()->subDays(2)->setTime(12, 40));

        $result = $this->service->checkAvailability($this->user->id);

        $this->assertTrue($result['available']);
        $this->assertEquals(2, $result['count']);
    }

    // ========== Tests nextTime ==========

    public function test_next_time_returns_first_slot_before_it(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(10, 0));

        $result = $this->service->checkAvailability($this->user->id);

        $this->assertEquals('12:35', $result['nextTime']);
    }

    public function test_next_time_returns_second_slot_between_slots(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(15, 0));

        $result = $this->service->checkAvailability($this->user->id);

        $this->assertEquals('18:35', $result['nextTime']);
    }

    public function test_next_time_wraps_to_next_day(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(20, 0));

        $result = $this->service->checkAvailability($this->user->id);

        $this->assertEquals('12:35', $result['nextTime']);
    }

    // ========== Tests d'isolation utilisateur ==========

    public function test_different_users_have_independent_availability(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(19, 0));

        $otherUser = User::factory()->mmi1()->create();

        // Premier utilisateur a ouvert une lootbox
        $this->createLootboxAt(Carbon::today()->setTime(12, 40));

        $resultUser1 = $this->service->checkAvailability($this->user->id);
        $resultUser2 = $this->service->checkAvailability($otherUser->id);

        $this->assertEquals(1, $resultUser1['count']);
        $this->assertEquals(2, $resultUser2['count']);
    }

    // ========== Scénario réel : utilisateur absent plusieurs jours ==========

    public function test_user_absent_multiple_days_gets_two_boosters(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(10, 0));

        // Dernière lootbox il y a 5 jours (hors période)
        $this->createLootboxAt(Carbon::today()->subDays(5)->setTime(13, 0));

        $result = $this->service->checkAvailability($this->user->id);

        $this->assertTrue($result['available']);
        $this->assertEquals(2, $result['count']);
    }

    public function test_user_opens_one_booster_then_has_one_left(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(10, 0));

        $result1 = $this->service->checkAvailability($this->user->id);
        $this->assertEquals(2, $result1['count']);

        // Simule l'ouverture d'un booster à 10h00
        $this->createLootboxAt(Carbon::today()->setTime(10, 0));

        $result2 = $this->service->checkAvailability($this->user->id);
        $this->assertEquals(1, $result2['count']);
    }

    public function test_new_slot_regenerates_after_opening(): void
    {
        // Utilisateur à 10h00, ouvre ses 2 boosters
        Carbon::setTestNow(Carbon::today()->setTime(10, 0));

        $this->createLootboxAt(Carbon::today()->setTime(10, 0));
        $this->createLootboxAt(Carbon::today()->setTime(10, 1));

        $result1 = $this->service->checkAvailability($this->user->id);
        $this->assertEquals(0, $result1['count']);

        // Avance à 19h00 (après les deux slots d'aujourd'hui)
        Carbon::setTestNow(Carbon::today()->setTime(19, 0));

        $result2 = $this->service->checkAvailability($this->user->id);
        // Les slots sont maintenant: 12:35 aujourd'hui, 18:35 aujourd'hui
        // Le plus ancien est 12:35 aujourd'hui
        // Les lootboxes de 10:00 et 10:01 sont AVANT 12:35 aujourd'hui
        // Donc elles ne comptent plus -> 2 slots disponibles
        $this->assertEquals(2, $result2['count']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
