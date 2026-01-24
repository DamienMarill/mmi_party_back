<?php

namespace Tests\Unit\Enums;

use App\Enums\UserGroups;
use PHPUnit\Framework\TestCase;

class UserGroupsTest extends TestCase
{
    // ========== Tests des valeurs ==========

    public function test_has_all_expected_groups(): void
    {
        $expected = ['student', 'staff', 'mmi1', 'mmi2', 'mmi3', 'misc'];
        $actual = UserGroups::values();

        $this->assertEquals($expected, $actual);
    }

    public function test_cases_count(): void
    {
        $this->assertCount(6, UserGroups::cases());
    }

    // ========== Tests des labels ==========

    public function test_student_label(): void
    {
        $this->assertEquals('Étudiant', UserGroups::STUDENT->label());
    }

    public function test_staff_label(): void
    {
        $this->assertEquals('Personnel', UserGroups::STAFF->label());
    }

    public function test_mmi1_label(): void
    {
        $this->assertEquals('MMI 1', UserGroups::MMI1->label());
    }

    public function test_mmi2_label(): void
    {
        $this->assertEquals('MMI 2', UserGroups::MMI2->label());
    }

    public function test_mmi3_label(): void
    {
        $this->assertEquals('MMI 3', UserGroups::MMI3->label());
    }

    public function test_misc_label(): void
    {
        $this->assertEquals('Divers', UserGroups::MISC->label());
    }

    // ========== Tests de conversion ==========

    public function test_can_create_from_string(): void
    {
        $this->assertEquals(UserGroups::STUDENT, UserGroups::from('student'));
        $this->assertEquals(UserGroups::MMI1, UserGroups::from('mmi1'));
        $this->assertEquals(UserGroups::MMI2, UserGroups::from('mmi2'));
        $this->assertEquals(UserGroups::MMI3, UserGroups::from('mmi3'));
    }

    public function test_try_from_returns_null_for_invalid(): void
    {
        $this->assertNull(UserGroups::tryFrom('mmi4'));
    }

    // ========== Tests metier ==========

    public function test_mmi_groups_exist(): void
    {
        $mmiGroups = ['mmi1', 'mmi2', 'mmi3'];

        foreach ($mmiGroups as $group) {
            $this->assertContains($group, UserGroups::values());
        }
    }
}
