<?php

namespace Tests\Unit\Services;

use App\Services\ShapeValidator;
use PHPUnit\Framework\TestCase;

class ShapeValidatorTest extends TestCase
{
    // ========== Tests de validation de format ==========

    public function test_rejects_empty_shape(): void
    {
        $this->assertFalse(ShapeValidator::isValid([], 1));
    }

    public function test_rejects_non_boolean_values(): void
    {
        $shape = [[1, 0], [0, 1]];
        $this->assertFalse(ShapeValidator::isValid($shape, 1));
    }

    public function test_rejects_inconsistent_row_lengths(): void
    {
        $shape = [[true, false], [true]];
        $this->assertFalse(ShapeValidator::isValid($shape, 1));
    }

    // ========== Tests de validation par niveau ==========

    public function test_mmi1_requires_2_blocks(): void
    {
        $validShape = [[true, true]];
        $invalidShape = [[true, true, true]];

        $this->assertTrue(ShapeValidator::isValid($validShape, 1));
        $this->assertFalse(ShapeValidator::isValid($invalidShape, 1));
    }

    public function test_mmi2_requires_3_blocks(): void
    {
        $validShape = [[true, true, true]];
        $invalidShape = [[true, true]];

        $this->assertTrue(ShapeValidator::isValid($validShape, 2));
        $this->assertFalse(ShapeValidator::isValid($invalidShape, 2));
    }

    public function test_mmi3_requires_4_blocks(): void
    {
        $validShape = [[true, true], [true, true]];
        $invalidShape = [[true, true, true]];

        $this->assertTrue(ShapeValidator::isValid($validShape, 3));
        $this->assertFalse(ShapeValidator::isValid($invalidShape, 3));
    }

    // ========== Tests de connexite ==========

    public function test_connected_horizontal_shape_is_valid(): void
    {
        $shape = [[true, true]];
        $this->assertTrue(ShapeValidator::isValid($shape, 1));
    }

    public function test_connected_vertical_shape_is_valid(): void
    {
        $shape = [[true], [true]];
        $this->assertTrue(ShapeValidator::isValid($shape, 1));
    }

    public function test_disconnected_blocks_are_invalid(): void
    {
        // Deux blocs non adjacents
        $shape = [
            [true, false, true]
        ];
        $this->assertFalse(ShapeValidator::isValid($shape, 1));
    }

    public function test_diagonal_blocks_are_not_connected(): void
    {
        // Blocs en diagonale (non connectes)
        $shape = [
            [true, false],
            [false, true]
        ];
        $this->assertFalse(ShapeValidator::isValid($shape, 1));
    }

    public function test_l_shape_is_valid_for_mmi2(): void
    {
        $shape = [
            [true, false],
            [true, true]
        ];
        $this->assertTrue(ShapeValidator::isValid($shape, 2));
    }

    public function test_t_shape_is_valid_for_mmi3(): void
    {
        $shape = [
            [false, true, false],
            [true, true, true]
        ];
        $this->assertTrue(ShapeValidator::isValid($shape, 3));
    }

    // ========== Tests de generation ==========

    public function test_generates_valid_shape_for_level_1(): void
    {
        $shape = ShapeValidator::generate(1);
        $this->assertTrue(ShapeValidator::isValid($shape, 1));
    }

    public function test_generates_valid_shape_for_level_2(): void
    {
        $shape = ShapeValidator::generate(2);
        $this->assertTrue(ShapeValidator::isValid($shape, 2));
    }

    public function test_generates_valid_shape_for_level_3(): void
    {
        $shape = ShapeValidator::generate(3);
        $this->assertTrue(ShapeValidator::isValid($shape, 3));
    }

    public function test_generate_throws_for_invalid_level(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ShapeValidator::generate(4);
    }

    // ========== Tests de robustesse ==========

    public function test_generated_shapes_are_always_valid(): void
    {
        // Test sur plusieurs iterations pour verifier la coherence
        for ($level = 1; $level <= 3; $level++) {
            for ($i = 0; $i < 10; $i++) {
                $shape = ShapeValidator::generate($level);
                $this->assertTrue(
                    ShapeValidator::isValid($shape, $level),
                    "Generated shape for level $level should be valid"
                );
            }
        }
    }
}
