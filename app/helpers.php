<?php

/**
 * Global helper functions for Indian number formatting
 */

if (!function_exists('format_indian')) {
    /**
     * Format number in Indian style (lakhs, crores)
     * 
     * @param float|int $number
     * @param int $decimals
     * @return string
     */
    function format_indian($number, $decimals = 2) {
        return \App\Helpers\NumberFormatHelper::formatIndian($number, $decimals);
    }
}

if (!function_exists('format_indian_currency')) {
    /**
     * Format currency in Indian style with Rs. prefix
     * 
     * @param float|int $number
     * @param int $decimals
     * @return string
     */
    function format_indian_currency($number, $decimals = 2) {
        return \App\Helpers\NumberFormatHelper::formatIndianCurrency($number, $decimals);
    }
}

if (!function_exists('format_indian_percentage')) {
    /**
     * Format percentage in Indian style
     * 
     * @param float|int $number
     * @param int $decimals
     * @return string
     */
    function format_indian_percentage($number, $decimals = 1) {
        return \App\Helpers\NumberFormatHelper::formatPercentage($number, $decimals);
    }
}

if (!function_exists('format_indian_integer')) {
    /**
     * Format number as integer in Indian style (no decimals)
     * 
     * @param float|int $number
     * @return string
     */
    function format_indian_integer($number) {
        return \App\Helpers\NumberFormatHelper::formatIndianInteger($number);
    }
}
