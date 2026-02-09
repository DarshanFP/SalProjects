<?php

namespace Tests\Unit\Validation;

use App\Rules\NumericBoundsRule;
use Tests\TestCase;

class NumericBoundsRuleTest extends TestCase
{
    private NumericBoundsRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new NumericBoundsRule;
    }

    public function test_accepts_zero(): void
    {
        $this->assertTrue($this->rule->passes('amount', 0));
        $this->assertTrue($this->rule->passes('amount', '0'));
    }

    public function test_accepts_one(): void
    {
        $this->assertTrue($this->rule->passes('amount', 1));
        $this->assertTrue($this->rule->passes('amount', '1'));
    }

    public function test_accepts_max_bound(): void
    {
        $this->assertTrue($this->rule->passes('amount', 99999999.99));
        $this->assertTrue($this->rule->passes('amount', '99999999.99'));
    }

    public function test_rejects_negative(): void
    {
        $this->assertFalse($this->rule->passes('amount', -1));
        $this->assertFalse($this->rule->passes('amount', '-1'));
    }

    public function test_rejects_above_max(): void
    {
        $this->assertFalse($this->rule->passes('amount', 100000000));
        $this->assertFalse($this->rule->passes('amount', 100000000.00));
        $this->assertFalse($this->rule->passes('amount', '100000000'));
    }

    public function test_rejects_non_numeric_string(): void
    {
        $this->assertFalse($this->rule->passes('amount', 'abc'));
        $this->assertFalse($this->rule->passes('amount', 'N/A'));
        $this->assertFalse($this->rule->passes('amount', '-'));
    }

    public function test_message_contains_bounds(): void
    {
        $message = $this->rule->message();
        $this->assertStringContainsString('0', $message);
        $this->assertStringContainsString('99999999.99', $message);
    }
}
