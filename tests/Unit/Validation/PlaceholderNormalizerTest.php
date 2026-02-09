<?php

namespace Tests\Unit\Validation;

use App\Support\Normalization\PlaceholderNormalizer;
use PHPUnit\Framework\TestCase;

class PlaceholderNormalizerTest extends TestCase
{
    public function test_recognizes_minus(): void
    {
        $this->assertTrue(PlaceholderNormalizer::isPlaceholder('-'));
    }

    public function test_recognizes_na_uppercase(): void
    {
        $this->assertTrue(PlaceholderNormalizer::isPlaceholder('N/A'));
    }

    public function test_recognizes_na_lowercase(): void
    {
        $this->assertTrue(PlaceholderNormalizer::isPlaceholder('n/a'));
    }

    public function test_recognizes_na_plain(): void
    {
        $this->assertTrue(PlaceholderNormalizer::isPlaceholder('NA'));
    }

    public function test_recognizes_double_minus(): void
    {
        $this->assertTrue(PlaceholderNormalizer::isPlaceholder('--'));
    }

    public function test_does_not_recognize_random_strings(): void
    {
        $this->assertFalse(PlaceholderNormalizer::isPlaceholder('hello'));
        $this->assertFalse(PlaceholderNormalizer::isPlaceholder('0'));
        $this->assertFalse(PlaceholderNormalizer::isPlaceholder('123'));
        $this->assertFalse(PlaceholderNormalizer::isPlaceholder(''));
    }

    public function test_does_not_recognize_null(): void
    {
        $this->assertFalse(PlaceholderNormalizer::isPlaceholder(null));
    }

    public function test_normalize_to_null_returns_null_for_placeholders(): void
    {
        $this->assertNull(PlaceholderNormalizer::normalizeToNull('-'));
        $this->assertNull(PlaceholderNormalizer::normalizeToNull('N/A'));
        $this->assertNull(PlaceholderNormalizer::normalizeToNull('--'));
    }

    public function test_normalize_to_zero_returns_zero_for_placeholders(): void
    {
        $this->assertSame(0, PlaceholderNormalizer::normalizeToZero('-'));
        $this->assertSame(0, PlaceholderNormalizer::normalizeToZero('N/A'));
    }
}
