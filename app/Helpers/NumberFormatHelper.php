<?php

namespace App\Helpers;

class NumberFormatHelper
{
    /**
     * Format number in Indian style (lakhs, crores)
     * Example: 1000000 becomes "10,00,000"
     * 
     * @param float|int $number
     * @param int $decimals Number of decimal places
     * @return string
     */
    public static function formatIndian($number, $decimals = 2)
    {
        if ($number == 0) {
            return number_format(0, $decimals, '.', '');
        }

        // Handle negative numbers
        $negative = $number < 0;
        $number = abs($number);

        // Split into integer and decimal parts
        $parts = explode('.', number_format($number, $decimals, '.', ''));
        $integerPart = $parts[0];
        $decimalPart = isset($parts[1]) ? $parts[1] : '';

        // Format integer part in Indian style
        $formattedInteger = '';
        $length = strlen($integerPart);
        
        if ($length <= 3) {
            $formattedInteger = $integerPart;
        } else {
            // First 3 digits from right
            $formattedInteger = substr($integerPart, -3);
            $remaining = substr($integerPart, 0, -3);
            
            // Then every 2 digits
            while (strlen($remaining) > 2) {
                $formattedInteger = substr($remaining, -2) . ',' . $formattedInteger;
                $remaining = substr($remaining, 0, -2);
            }
            
            if (strlen($remaining) > 0) {
                $formattedInteger = $remaining . ',' . $formattedInteger;
            }
        }

        $result = $formattedInteger;
        if ($decimals > 0 && !empty($decimalPart)) {
            $result .= '.' . $decimalPart;
        } elseif ($decimals > 0 && empty($decimalPart)) {
            // Add decimal part with zeros if needed
            $result .= '.' . str_repeat('0', $decimals);
        }

        return ($negative ? '-' : '') . $result;
    }

    /**
     * Format currency in Indian style with Rs. prefix
     * 
     * @param float|int $number
     * @param int $decimals
     * @return string
     */
    public static function formatIndianCurrency($number, $decimals = 2)
    {
        return 'Rs. ' . self::formatIndian($number, $decimals);
    }

    /**
     * Format percentage in Indian style
     * 
     * @param float|int $number
     * @param int $decimals
     * @return string
     */
    public static function formatPercentage($number, $decimals = 1)
    {
        return self::formatIndian($number, $decimals) . '%';
    }

    /**
     * Format number as integer in Indian style (no decimals)
     * 
     * @param float|int $number
     * @return string
     */
    public static function formatIndianInteger($number)
    {
        return self::formatIndian($number, 0);
    }
}
