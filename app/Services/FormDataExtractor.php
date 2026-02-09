<?php

namespace App\Services;

use App\Support\Normalization\ArrayToScalarNormalizer;
use Illuminate\Http\Request;

/**
 * Extracts, scopes, and normalizes request data for model fill().
 *
 * Centralizes the Phase 1A pattern: $request->only($fillable) + ArrayToScalarNormalizer.
 * Prevents array-to-scalar conversion and mass assignment from unrelated form sections.
 *
 * @see Documentations/V2/Implementations/Phase_2/FormDataExtractor.md
 */
class FormDataExtractor
{
    /**
     * Extract allowed keys from request and apply normalizers.
     *
     * @param  Request  $request
     * @param  array<string>  $allowedKeys
     * @param  array<callable(array, array): array>  $normalizers  Ordered list; each receives (data, keys), returns data
     * @return array<string, string|int|float|bool|null>  Scalar-only array safe for fill()
     */
    public static function extract(Request $request, array $allowedKeys, array $normalizers = []): array
    {
        $data = $request->only($allowedKeys);

        return static::normalize($data, $allowedKeys, $normalizers);
    }

    /**
     * Convenience: extract with default normalizers (ArrayToScalar only, matching Phase 1A behavior).
     *
     * @param  Request  $request
     * @param  array<string>  $fillable  Allowed keys (equivalent to model fillable)
     * @param  array<callable(array, array): array>  $normalizers  Optional; when empty, uses ArrayToScalar only
     * @return array<string, string|int|float|bool|null>  Scalar-only array safe for fill()
     */
    public static function forFillable(Request $request, array $fillable, array $normalizers = []): array
    {
        $data = $request->only($fillable);

        if (empty($normalizers)) {
            return ArrayToScalarNormalizer::forFillable($data, $fillable);
        }

        return static::normalize($data, $fillable, $normalizers);
    }

    /**
     * Normalize already-extracted data with the given normalizers.
     *
     * @param  array<string, mixed>  $data  Raw data (e.g. from $request->only() or validated())
     * @param  array<string>  $keys  Keys to process
     * @param  array<callable(array, array): array>  $normalizers  Ordered list; each receives (data, keys), returns data
     * @return array<string, string|int|float|bool|null>  Scalar-only array safe for fill()
     */
    public static function normalize(array $data, array $keys, array $normalizers): array
    {
        if (empty($normalizers)) {
            return ArrayToScalarNormalizer::forFillable($data, $keys);
        }

        $result = $data;

        foreach ($normalizers as $normalizer) {
            $result = $normalizer($result, $keys);
        }

        return $result;
    }
}
