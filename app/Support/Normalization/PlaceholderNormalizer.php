<?php

namespace App\Support\Normalization;

class PlaceholderNormalizer
{
    private const PLACEHOLDERS = ['-', 'N/A', 'n/a', 'NA', '--'];

    /**
     * Check if the value is in the canonical placeholder set.
     *
     * @param  mixed  $value
     * @return bool
     */
    public static function isPlaceholder($value): bool
    {
        if ($value === null) {
            return false;
        }

        $trimmed = is_string($value) ? trim($value) : $value;

        return in_array($trimmed, self::PLACEHOLDERS, true);
    }

    /**
     * Return null if value is empty or placeholder; otherwise return the value unchanged.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public static function normalizeToNull($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value) && trim($value) === '') {
            return null;
        }

        if (self::isPlaceholder($value)) {
            return null;
        }

        return $value;
    }

    /**
     * Return 0 if value is empty or placeholder; otherwise return the value unchanged.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public static function normalizeToZero($value)
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_string($value) && trim($value) === '') {
            return 0;
        }

        if (self::isPlaceholder($value)) {
            return 0;
        }

        return $value;
    }
}
