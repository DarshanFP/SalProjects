<?php

namespace Tests\Unit\Validation;

use App\Support\Normalization\NumericNormalizer;
use PHPUnit\Framework\TestCase;

class NumericNormalizerTest extends TestCase
{
    public function test_empty_to_zero_returns_zero_for_null(): void
    {
        $this->assertSame(0, NumericNormalizer::emptyToZero(null));
    }

    public function test_empty_to_zero_returns_zero_for_empty_string(): void
    {
        $this->assertSame(0, NumericNormalizer::emptyToZero(''));
    }

    public function test_empty_to_zero_returns_value_otherwise(): void
    {
        $this->assertSame(42, NumericNormalizer::emptyToZero(42));
        $this->assertSame('1', NumericNormalizer::emptyToZero('1'));
    }

    public function test_empty_to_null_returns_null_for_null(): void
    {
        $this->assertNull(NumericNormalizer::emptyToNull(null));
    }

    public function test_empty_to_null_returns_null_for_empty_string(): void
    {
        $this->assertNull(NumericNormalizer::emptyToNull(''));
    }

    public function test_empty_to_null_returns_value_otherwise(): void
    {
        $this->assertSame(42, NumericNormalizer::emptyToNull(42));
    }
}
