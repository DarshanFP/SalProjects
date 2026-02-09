<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class OptionalIntegerRule implements Rule
{
    /**
     * Determine if the validation rule passes.
     * Accepts null and integers >= 0. Rejects non-integers and negative integers.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (! is_numeric($value)) {
            return false;
        }

        $intVal = (int) $value;
        $floatVal = (float) $value;

        if ((float) $intVal !== $floatVal) {
            return false;
        }

        return $intVal >= 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be a non-negative integer or empty.';
    }
}
