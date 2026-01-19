<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\NumberFormatHelper;

class NumberFormatHelperTest extends TestCase
{
    /**
     * Test formatting thousands (1,000 - 9,999)
     */
    public function test_format_indian_with_thousands()
    {
        $this->assertEquals('1,000', NumberFormatHelper::formatIndian(1000, 0));
        $this->assertEquals('5,000', NumberFormatHelper::formatIndian(5000, 0));
        $this->assertEquals('9,999', NumberFormatHelper::formatIndian(9999, 0));
    }

    /**
     * Test formatting ten thousands (10,000 - 99,999)
     */
    public function test_format_indian_with_ten_thousands()
    {
        $this->assertEquals('10,000', NumberFormatHelper::formatIndian(10000, 0));
        $this->assertEquals('50,000', NumberFormatHelper::formatIndian(50000, 0));
        $this->assertEquals('99,999', NumberFormatHelper::formatIndian(99999, 0));
    }

    /**
     * Test formatting lakhs (1,00,000 - 99,99,999)
     */
    public function test_format_indian_with_lakhs()
    {
        $this->assertEquals('1,00,000', NumberFormatHelper::formatIndian(100000, 0));
        $this->assertEquals('10,00,000', NumberFormatHelper::formatIndian(1000000, 0));
        $this->assertEquals('50,00,000', NumberFormatHelper::formatIndian(5000000, 0));
        $this->assertEquals('99,99,999', NumberFormatHelper::formatIndian(9999999, 0));
    }

    /**
     * Test formatting crores (1,00,00,000+)
     */
    public function test_format_indian_with_crores()
    {
        $this->assertEquals('1,00,00,000', NumberFormatHelper::formatIndian(10000000, 0));
        $this->assertEquals('10,00,00,000', NumberFormatHelper::formatIndian(100000000, 0));
        $this->assertEquals('1,00,00,00,000', NumberFormatHelper::formatIndian(1000000000, 0));
    }

    /**
     * Test formatting with decimals
     */
    public function test_format_indian_with_decimals()
    {
        $this->assertEquals('1,00,000.50', NumberFormatHelper::formatIndian(100000.50, 2));
        $this->assertEquals('10,00,000.99', NumberFormatHelper::formatIndian(1000000.99, 2));
        $this->assertEquals('1,23,456.78', NumberFormatHelper::formatIndian(123456.78, 2));
    }

    /**
     * Test formatting currency
     */
    public function test_format_indian_currency()
    {
        $this->assertEquals('Rs. 10,00,000.00', NumberFormatHelper::formatIndianCurrency(1000000, 2));
        $this->assertEquals('Rs. 1,00,000', NumberFormatHelper::formatIndianCurrency(100000, 0));
        $this->assertEquals('Rs. 50,000.50', NumberFormatHelper::formatIndianCurrency(50000.50, 2));
    }

    /**
     * Test formatting percentage
     */
    public function test_format_indian_percentage()
    {
        $this->assertEquals('85.5%', NumberFormatHelper::formatPercentage(85.5, 1));
        $this->assertEquals('100.00%', NumberFormatHelper::formatPercentage(100, 2));
        $this->assertEquals('0.5%', NumberFormatHelper::formatPercentage(0.5, 1));
    }

    /**
     * Test formatting integer
     */
    public function test_format_indian_integer()
    {
        $this->assertEquals('12,34,567', NumberFormatHelper::formatIndianInteger(1234567));
        $this->assertEquals('1,00,000', NumberFormatHelper::formatIndianInteger(100000));
        $this->assertEquals('1,00,00,000', NumberFormatHelper::formatIndianInteger(10000000));
    }

    /**
     * Test with zero
     */
    public function test_format_indian_with_zero()
    {
        $this->assertEquals('0.00', NumberFormatHelper::formatIndian(0, 2));
        $this->assertEquals('Rs. 0.00', NumberFormatHelper::formatIndianCurrency(0, 2));
        $this->assertEquals('0', NumberFormatHelper::formatIndian(0, 0));
    }

    /**
     * Test with negative numbers
     */
    public function test_format_indian_with_negative()
    {
        $this->assertEquals('-10,00,000.00', NumberFormatHelper::formatIndian(-1000000, 2));
        $this->assertEquals('Rs. -1,00,000.50', NumberFormatHelper::formatIndianCurrency(-100000.50, 2));
    }

    /**
     * Test with very small numbers
     */
    public function test_format_indian_with_small_numbers()
    {
        $this->assertEquals('100', NumberFormatHelper::formatIndian(100, 0));
        $this->assertEquals('999', NumberFormatHelper::formatIndian(999, 0));
        $this->assertEquals('100.50', NumberFormatHelper::formatIndian(100.50, 2));
    }

    /**
     * Test with various decimal places
     */
    public function test_format_indian_with_various_decimals()
    {
        $this->assertEquals('10,00,000', NumberFormatHelper::formatIndian(1000000, 0));
        $this->assertEquals('10,00,000.0', NumberFormatHelper::formatIndian(1000000, 1));
        $this->assertEquals('10,00,000.00', NumberFormatHelper::formatIndian(1000000, 2));
        $this->assertEquals('10,00,000.000', NumberFormatHelper::formatIndian(1000000, 3));
    }

    /**
     * Test edge case with exact boundary values
     */
    public function test_format_indian_boundary_values()
    {
        // Boundary between thousands and lakhs
        $this->assertEquals('99,999', NumberFormatHelper::formatIndian(99999, 0));
        $this->assertEquals('1,00,000', NumberFormatHelper::formatIndian(100000, 0));
        
        // Boundary between lakhs and crores
        $this->assertEquals('99,99,999', NumberFormatHelper::formatIndian(9999999, 0));
        $this->assertEquals('1,00,00,000', NumberFormatHelper::formatIndian(10000000, 0));
    }
}
