<?php

namespace Tests\Feature\Lootbox;

use App\Enums\CardRarity;
use App\Enums\CardTypes;
use App\Models\CardTemplate;
use App\Models\CardVersion;
use App\Models\Mmii;
use App\Services\LootboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test statistique du système de lootbox basé sur la rareté.
 *
 * Le nouveau système sélectionne directement une rareté par slot,
 * puis pioche une CardVersion random de cette rareté.
 */
class LootboxStatisticsTest extends TestCase
{
    use RefreshDatabase;

    private LootboxService $service;

    private const DRAWS = 1000;
    private const Z_SCORE = 2.576;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LootboxService();
        $this->seedTestData();
    }

    /**
     * Seed des CardVersions pour chaque rareté afin que le service puisse tirer.
     */
    private function seedTestData(): void
    {
        Mmii::factory(10)->create();
        $mmiis = Mmii::all();

        // Common : 15 versions (students L1/L2, objets)
        for ($i = 0; $i < 15; $i++) {
            $template = CardTemplate::factory()->student()->withLevel(1)->withMmii($mmiis->random())->create();
            CardVersion::factory()->forTemplate($template->id)->withRarity(CardRarity::COMMON)->create();
        }

        // Uncommon : 10 versions (students L2)
        for ($i = 0; $i < 10; $i++) {
            $template = CardTemplate::factory()->student()->withLevel(2)->withMmii($mmiis->random())->create();
            CardVersion::factory()->forTemplate($template->id)->withRarity(CardRarity::UNCOMMON)->create();
        }

        // Rare : 8 versions (students L3, staff)
        for ($i = 0; $i < 8; $i++) {
            $template = CardTemplate::factory()->student()->withLevel(3)->withMmii($mmiis->random())->create();
            CardVersion::factory()->forTemplate($template->id)->withRarity(CardRarity::RARE)->create();
        }

        // Epic : 5 versions (staff)
        for ($i = 0; $i < 5; $i++) {
            $template = CardTemplate::factory()->staff()->withMmii($mmiis->random())->create();
            CardVersion::factory()->forTemplate($template->id)->withRarity(CardRarity::EPIC)->create();
        }
    }

    private function marginOfError(float $expectedProportion, int $n): float
    {
        if ($expectedProportion <= 0 || $expectedProportion >= 1) {
            return 0.01;
        }
        return self::Z_SCORE * sqrt($expectedProportion * (1 - $expectedProportion) / $n);
    }

    private function assertProportionInRange(float $observed, float $expected, int $n, string $label): void
    {
        $margin = $this->marginOfError($expected, $n);
        $lower = max(0, $expected - $margin);
        $upper = min(1, $expected + $margin);

        $this->assertTrue(
            $observed >= $lower && $observed <= $upper,
            sprintf(
                "%s: observé %.4f hors de [%.4f, %.4f] (attendu: %.4f, n=%d)",
                $label, $observed, $lower, $upper, $expected, $n
            )
        );
    }

    // ==========================================
    // Test par slot : distribution des raretés
    // ==========================================

    public function test_slot0_always_common(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $version = $this->service->generateLoot(0);
            $this->assertEquals(CardRarity::COMMON, $version->rarity, "Slot 0 doit toujours donner common");
        }
    }

    public function test_slot1_always_common(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $version = $this->service->generateLoot(1);
            $this->assertEquals(CardRarity::COMMON, $version->rarity, "Slot 1 doit toujours donner common");
        }
    }

    public function test_slot2_rarity_distribution(): void
    {
        $results = ['common' => 0, 'uncommon' => 0, 'rare' => 0, 'epic' => 0];

        for ($i = 0; $i < self::DRAWS; $i++) {
            $version = $this->service->generateLoot(2);
            $results[$version->rarity->value]++;
        }

        $expected = config('app.loot_rate')[2];

        foreach ($expected as $rarity => $rate) {
            if ($rate > 0) {
                $this->assertProportionInRange(
                    $results[$rarity] / self::DRAWS, $rate, self::DRAWS,
                    "Slot 2 → {$rarity} (attendu: " . ($rate * 100) . "%)"
                );
            }
        }

        echo "\n  Slot 2 rarity distribution (n=" . self::DRAWS . "):\n";
        foreach ($results as $rarity => $count) {
            printf("    %-12s: %4d (%5.1f%%)\n", $rarity, $count, $count / self::DRAWS * 100);
        }
    }

    public function test_slot3_rarity_distribution(): void
    {
        $results = ['common' => 0, 'uncommon' => 0, 'rare' => 0, 'epic' => 0];

        for ($i = 0; $i < self::DRAWS; $i++) {
            $version = $this->service->generateLoot(3);
            $results[$version->rarity->value]++;
        }

        $expected = config('app.loot_rate')[3];

        foreach ($expected as $rarity => $rate) {
            if ($rate > 0) {
                $this->assertProportionInRange(
                    $results[$rarity] / self::DRAWS, $rate, self::DRAWS,
                    "Slot 3 → {$rarity} (attendu: " . ($rate * 100) . "%)"
                );
            }
        }

        echo "\n  Slot 3 rarity distribution (n=" . self::DRAWS . "):\n";
        foreach ($results as $rarity => $count) {
            printf("    %-12s: %4d (%5.1f%%)\n", $rarity, $count, $count / self::DRAWS * 100);
        }
    }

    public function test_slot4_rarity_distribution(): void
    {
        $results = ['common' => 0, 'uncommon' => 0, 'rare' => 0, 'epic' => 0];

        for ($i = 0; $i < self::DRAWS; $i++) {
            $version = $this->service->generateLoot(4);
            $results[$version->rarity->value]++;
        }

        $expected = config('app.loot_rate')[4];

        foreach ($expected as $rarity => $rate) {
            if ($rate > 0) {
                $this->assertProportionInRange(
                    $results[$rarity] / self::DRAWS, $rate, self::DRAWS,
                    "Slot 4 → {$rarity} (attendu: " . ($rate * 100) . "%)"
                );
            }
        }

        echo "\n  Slot 4 rarity distribution (n=" . self::DRAWS . "):\n";
        foreach ($results as $rarity => $count) {
            printf("    %-12s: %4d (%5.1f%%)\n", $rarity, $count, $count / self::DRAWS * 100);
        }
    }

    // ==========================================
    // Simulation complète
    // ==========================================

    public function test_full_lootbox_simulation(): void
    {
        $totalLootboxes = self::DRAWS;
        $totalCards = $totalLootboxes * 5;

        $rarityDistribution = ['common' => 0, 'uncommon' => 0, 'rare' => 0, 'epic' => 0];
        $slotDistribution = [0 => [], 1 => [], 2 => [], 3 => [], 4 => []];

        for ($i = 0; $i < $totalLootboxes; $i++) {
            $lootbox = $this->service->generateLootbox();

            foreach ($lootbox as $slotIndex => $version) {
                $rarityDistribution[$version->rarity->value]++;
                $key = $version->rarity->value;
                $slotDistribution[$slotIndex][$key] = ($slotDistribution[$slotIndex][$key] ?? 0) + 1;
            }
        }

        // On doit avoir au moins quelques epic dans 5000 cartes
        $this->assertGreaterThan(0, $rarityDistribution['epic'],
            "Sur {$totalCards} cartes, on devrait avoir au moins une epic");

        // Rapport
        echo "\n\n  === RAPPORT SIMULATION ({$totalLootboxes} lootboxes = {$totalCards} cartes) ===\n\n";

        echo "  Distribution globale des raretés:\n";
        foreach ($rarityDistribution as $rarity => $count) {
            $bar = str_repeat('█', (int) ($count / $totalCards * 100));
            printf("    %-12s: %5d (%5.1f%%) %s\n", $rarity, $count, $count / $totalCards * 100, $bar);
        }

        echo "\n  Distribution par slot:\n";
        foreach ($slotDistribution as $slot => $rarities) {
            echo "    Slot {$slot}:\n";
            arsort($rarities);
            foreach ($rarities as $rarity => $count) {
                printf("      %-12s: %5d (%5.1f%%)\n", $rarity, $count, $count / $totalLootboxes * 100);
            }
        }

        $this->assertTrue(true);
    }
}
