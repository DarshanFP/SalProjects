<?php

namespace Tests\Unit\Validation;

use App\Support\Normalization\BooleanNormalizer;
use PHPUnit\Framework\TestCase;

class BooleanNormalizerTest extends TestCase
{
    public function test_true_returns_one(): void
    {
        $this->assertSame(1, BooleanNormalizer::toInt('true'));
        $this->assertSame(1, BooleanNormalizer::toInt(true));
        $this->assertSame(1, BooleanNormalizer::toInt(1));
    }

    public function test_false_returns_zero(): void
    {
        $this->assertSame(0, BooleanNormalizer::toInt('false'));
        $this->assertSame(0, BooleanNormalizer::toInt(false));
        $this->assertSame(0, BooleanNormalizer::toInt(0));
    }

    public function test_on_returns_one(): void
    {
        $this->assertSame(1, BooleanNormalizer::toInt('on'));
    }

    public function test_off_returns_zero(): void
    {
        $this->assertSame(0, BooleanNormalizer::toInt('off'));
    }

    public function test_null_returns_zero(): void
    {
        $this->assertSame(0, BooleanNormalizer::toInt(null));
    }
}
