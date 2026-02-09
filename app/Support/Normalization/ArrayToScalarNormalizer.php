<?php

namespace App\Support\Normalization;

/**
 * Normalizes array values to scalars for fillable model attributes.
 * Prevents "Array to string conversion" when filling models from multi-step forms
 * where other sections may submit array values (e.g. field_name[]).
 *
 * @see Phase_Wise_Refactor_Plan.md → Phase 0 → 0.5
 * @see Production_Errors_Analysis_070226.md → Error 3
 */
class ArrayToScalarNormalizer
{
    /**
     * For each key in $fillable, if the value is an array, convert to scalar (first element or null).
     *
     * @param  array<string, mixed>  $data  Raw input data (e.g. from $request->only($fillable))
     * @param  array<string>  $fillable  List of fillable attribute keys
     * @return array<string, mixed>  Data safe for model fill()
     */
    public static function forFillable(array $data, array $fillable): array
    {
        $result = [];

        foreach ($fillable as $key) {
            $value = $data[$key] ?? null;

            if (is_array($value)) {
                $result[$key] = reset($value) ?? null;
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
