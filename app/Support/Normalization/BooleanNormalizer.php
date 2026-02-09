<?php

namespace App\Support\Normalization;

class BooleanNormalizer
{
    /**
     * Normalize a value to int 0 or 1.
     * Accepts: "true", "false", "1", "0", "on", "off", null, and equivalent types.
     *
     * @param  mixed  $value
     * @return int
     */
    public static function toInt($value): int
    {
        $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($bool === null) {
            return 0;
        }

        return $bool ? 1 : 0;
    }
}
