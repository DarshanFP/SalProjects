<?php

namespace App\Support\Normalization;

class NumericNormalizer
{
    /**
     * Return 0 if value is empty (null or empty string); otherwise return the value unchanged.
     * Pure function: does not mutate input.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public static function emptyToZero($value)
    {
        if ($value === null) {
            return 0;
        }

        if ($value === '') {
            return 0;
        }

        if (is_string($value) && trim($value) === '') {
            return 0;
        }

        return $value;
    }

    /**
     * Return null if value is empty (null or empty string); otherwise return the value unchanged.
     * Pure function: does not mutate input.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public static function emptyToNull($value)
    {
        if ($value === null) {
            return null;
        }

        if ($value === '') {
            return null;
        }

        if (is_string($value) && trim($value) === '') {
            return null;
        }

        return $value;
    }
}
