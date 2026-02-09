<?php

namespace App\Rules;

use App\Services\Numeric\BoundedNumericService;
use Illuminate\Contracts\Validation\Rule;

class NumericBoundsRule implements Rule
{
    private ?string $fieldIdentifier;

    private ?float $min = null;

    private ?float $max = null;

    /**
     * @param  string|null  $fieldIdentifier  e.g. 'project_budgets.this_phase'. When omitted, uses default bounds.
     */
    public function __construct(?string $fieldIdentifier = null)
    {
        $this->fieldIdentifier = $fieldIdentifier ?? 'default';
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (! is_numeric($value)) {
            return false;
        }

        $service = app(BoundedNumericService::class);
        $min = $service->getMinFor($this->fieldIdentifier);
        $max = $service->getMaxFor($this->fieldIdentifier);

        $this->min = $min;
        $this->max = $max;

        $numeric = (float) $value;

        return $numeric >= $min && $numeric <= $max;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        $min = $this->min ?? app(BoundedNumericService::class)->getMinFor($this->fieldIdentifier);
        $max = $this->max ?? app(BoundedNumericService::class)->getMaxFor($this->fieldIdentifier);

        return 'The :attribute must be a number between ' . $min . ' and ' . number_format($max, 2, '.', '') . '.';
    }
}
