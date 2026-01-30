<?php

namespace Tests\Unit\Enums;

use App\Enums\CardTypes;
use PHPUnit\Framework\TestCase;

class CardTypesTest extends TestCase
{
    // ========== Tests des valeurs ==========

    public function test_has_all_expected_types(): void
    {
        $expected = ['student', 'staff', 'object'];
        $actual = CardTypes::values();

        $this->assertEquals($expected, $actual);
    }

    public function test_cases_count(): void
    {
        $this->assertCount(3, CardTypes::cases());
    }

    // ========== Tests des labels ==========

    public function test_student_label(): void
    {
        $this->assertEquals('Étudiant', CardTypes::STUDENT->label());
    }

    public function test_staff_label(): void
    {
        $this->assertEquals('Personnel', CardTypes::STAFF->label());
    }

    public function test_object_label(): void
    {
        $this->assertEquals('Objet', CardTypes::OBJECT->label());
    }

    // ========== Tests de l'ordre ==========

    public function test_ordered_values(): void
    {
        $ordered = CardTypes::getOrderedValues();

        $this->assertEquals('student', $ordered[0]);
        $this->assertEquals('staff', $ordered[1]);
        $this->assertEquals('object', $ordered[2]);
    }

    // ========== Tests de conversion ==========

    public function test_can_create_from_string(): void
    {
        $this->assertEquals(CardTypes::STUDENT, CardTypes::from('student'));
        $this->assertEquals(CardTypes::STAFF, CardTypes::from('staff'));
        $this->assertEquals(CardTypes::OBJECT, CardTypes::from('object'));
    }

    public function test_try_from_returns_null_for_invalid(): void
    {
        $this->assertNull(CardTypes::tryFrom('invalid'));
    }
}
