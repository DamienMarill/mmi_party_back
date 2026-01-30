<?php

namespace Tests\Unit\Enums;

use App\Enums\CardRarity;
use PHPUnit\Framework\TestCase;

class CardRarityTest extends TestCase
{
    // ========== Tests des valeurs ==========

    public function test_has_all_expected_rarities(): void
    {
        $expected = ['common', 'uncommon', 'rare', 'epic', 'legendary'];
        $actual = CardRarity::values();

        $this->assertEquals($expected, $actual);
    }

    public function test_cases_count(): void
    {
        $this->assertCount(5, CardRarity::cases());
    }

    // ========== Tests des labels ==========

    public function test_common_label(): void
    {
        $this->assertEquals('Commune', CardRarity::COMMON->label());
    }

    public function test_uncommon_label(): void
    {
        $this->assertEquals('Peu commune', CardRarity::UNCOMMON->label());
    }

    public function test_rare_label(): void
    {
        $this->assertEquals('Rare', CardRarity::RARE->label());
    }

    public function test_epic_label(): void
    {
        $this->assertEquals('Épique', CardRarity::EPIC->label());
    }

    public function test_legendary_label(): void
    {
        $this->assertEquals('Légendaire', CardRarity::LEGENDARY->label());
    }

    // ========== Tests des drop rates ==========

    public function test_common_drop_rate(): void
    {
        $this->assertEquals(0.70, CardRarity::COMMON->dropRate());
    }

    public function test_uncommon_drop_rate(): void
    {
        $this->assertEquals(0.20, CardRarity::UNCOMMON->dropRate());
    }

    public function test_rare_drop_rate(): void
    {
        $this->assertEquals(0.08, CardRarity::RARE->dropRate());
    }

    public function test_epic_drop_rate(): void
    {
        $this->assertEquals(0.02, CardRarity::EPIC->dropRate());
    }

    public function test_legendary_drop_rate(): void
    {
        $this->assertEquals(0, CardRarity::LEGENDARY->dropRate());
    }

    public function test_drop_rates_sum_to_one(): void
    {
        $total = array_sum(array_map(
            fn(CardRarity $rarity) => $rarity->dropRate(),
            CardRarity::cases()
        ));

        $this->assertEqualsWithDelta(1.0, $total, 0.0001);
    }

    // ========== Tests de l'ordre ==========

    public function test_ordered_values_from_common_to_legendary(): void
    {
        $ordered = CardRarity::getOrderedValues();

        $this->assertEquals('common', $ordered[0]);
        $this->assertEquals('legendary', $ordered[4]);
    }

    public function test_drop_rates_decrease_with_rarity(): void
    {
        $rates = [
            CardRarity::COMMON->dropRate(),
            CardRarity::UNCOMMON->dropRate(),
            CardRarity::RARE->dropRate(),
            CardRarity::EPIC->dropRate(),
            CardRarity::LEGENDARY->dropRate(),
        ];

        for ($i = 0; $i < count($rates) - 1; $i++) {
            $this->assertGreaterThanOrEqual(
                $rates[$i + 1],
                $rates[$i],
                "Drop rate should decrease or stay same as rarity increases"
            );
        }
    }
}
