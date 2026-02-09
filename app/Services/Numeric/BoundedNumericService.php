<?php

namespace App\Services\Numeric;

use Illuminate\Support\Facades\Config;

class BoundedNumericService
{
    private const CONFIG_KEY = 'decimal_bounds';

    /**
     * Returns configured max for field (e.g. 'project_budgets.this_phase').
     * If not configured, returns safe default (99999999.99 for decimal(10,2)).
     */
    public function getMaxFor(string $fieldIdentifier): float
    {
        $bounds = $this->resolveBounds($fieldIdentifier);

        return (float) ($bounds['max'] ?? Config::get(self::CONFIG_KEY . '.default.max', 99999999.99));
    }

    /**
     * Returns configured min for field (default 0 for non-negative fields).
     */
    public function getMinFor(string $fieldIdentifier): float
    {
        $bounds = $this->resolveBounds($fieldIdentifier);

        return (float) ($bounds['min'] ?? Config::get(self::CONFIG_KEY . '.default.min', 0));
    }

    /**
     * Returns value clamped to [min, max]. Pure function; no I/O.
     */
    public function clamp(float $value, float $max, float $min = 0): float
    {
        return max($min, min($max, $value));
    }

    /**
     * Evaluates formula($inputs), then clamps result to [min, max].
     * Used for derived fields (e.g. this_phase = rate_quantity * rate_multiplier * rate_duration).
     */
    public function calculateAndClamp(callable $formula, array $inputs, float $max, float $min = 0): float
    {
        $result = $formula(...$inputs);

        return $this->clamp((float) $result, $max, $min);
    }

    /**
     * Resolve bounds for field identifier (e.g. 'project_budgets.this_phase' or 'default').
     */
    private function resolveBounds(string $fieldIdentifier): array
    {
        if ($fieldIdentifier === 'default') {
            return Config::get(self::CONFIG_KEY . '.default', [
                'min' => 0,
                'max' => 99999999.99,
            ]);
        }

        $parts = explode('.', $fieldIdentifier, 2);
        if (count($parts) !== 2) {
            return Config::get(self::CONFIG_KEY . '.default', [
                'min' => 0,
                'max' => 99999999.99,
            ]);
        }

        [$table, $field] = $parts;
        $bounds = Config::get(self::CONFIG_KEY . '.' . $table . '.' . $field);

        if ($bounds !== null) {
            return $bounds;
        }

        return Config::get(self::CONFIG_KEY . '.default', [
            'min' => 0,
            'max' => 99999999.99,
        ]);
    }
}
