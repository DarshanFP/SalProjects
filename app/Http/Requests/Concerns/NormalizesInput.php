<?php

namespace App\Http\Requests\Concerns;

trait NormalizesInput
{
    /**
     * Normalize request input before validation.
     * Override in consuming FormRequest to apply normalization.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function normalizeInput(array $input): array
    {
        return $input;
    }

    /**
     * Merge normalized input into request before rules() run.
     */
    protected function prepareForValidation(): void
    {
        $this->merge($this->normalizeInput($this->all()));
    }

    /**
     * Return normalized input for Strategy B (validation outside route).
     *
     * @return array<string, mixed>
     */
    public function getNormalizedInput(): array
    {
        return $this->normalizeInput($this->all());
    }
}
