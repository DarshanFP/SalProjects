<?php

namespace Tests\Unit\Validation;

use App\Rules\OptionalIntegerRule;
use PHPUnit\Framework\TestCase;

class OptionalIntegerRuleTest extends TestCase
{
    private OptionalIntegerRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new OptionalIntegerRule;
    }

    public function test_accepts_null(): void
    {
        $this->assertTrue($this->rule->passes('count', null));
    }

    public function test_accepts_empty_string(): void
    {
        $this->assertTrue($this->rule->passes('count', ''));
    }

    public function test_accepts_zero_and_positive_integers(): void
    {
        $this->assertTrue($this->rule->passes('count', 0));
        $this->assertTrue($this->rule->passes('count', 1));
        $this->assertTrue($this->rule->passes('count', 100));
        $this->assertTrue($this->rule->passes('count', '0'));
        $this->assertTrue($this->rule->passes('count', '42'));
    }

    public function test_rejects_negative_integer(): void
    {
        $this->assertFalse($this->rule->passes('count', -1));
        $this->assertFalse($this->rule->passes('count', '-1'));
    }

    public function test_rejects_non_integer_string(): void
    {
        $this->assertFalse($this->rule->passes('count', 'abc'));
        $this->assertFalse($this->rule->passes('count', 'N/A'));
    }

    public function test_rejects_float(): void
    {
        $this->assertFalse($this->rule->passes('count', 1.5));
        $this->assertFalse($this->rule->passes('count', '1.5'));
    }

    public function test_message_returns_string(): void
    {
        $this->assertIsString($this->rule->message());
    }
}
