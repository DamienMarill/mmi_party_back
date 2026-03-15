<?php

namespace Tests\Unit\Enums;

use App\Enums\CardRarity;
use PHPUnit\Framework\TestCase;

class CardRarityTest extends TestCase
{
    public function test_has_all_expected_rarities(): void
    {
        $expected = ['common', 'uncommon', 'rare', 'epic'];
        $actual = CardRarity::values();

        $this->assertEquals($expected, $actual);
    }

    public function test_cases_count(): void
    {
        $this->assertCount(4, CardRarity::cases());
    }

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

    public function test_ordered_values_from_common_to_epic(): void
    {
        $ordered = CardRarity::getOrderedValues();

        $this->assertEquals('common', $ordered[0]);
        $this->assertEquals('epic', $ordered[3]);
        $this->assertCount(4, $ordered);
    }
}
