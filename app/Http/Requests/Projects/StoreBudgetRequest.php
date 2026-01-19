<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;

class StoreBudgetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow authenticated users to create budget
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'phases' => 'array',
            'phases.*.amount_sanctioned' => 'nullable|numeric|min:0',
            'phases.*.budget' => 'nullable|array',
            'phases.*.budget.*.particular' => 'nullable|string|max:255',
            'phases.*.budget.*.rate_quantity' => 'nullable|numeric|min:0',
            'phases.*.budget.*.rate_multiplier' => 'nullable|numeric|min:0',
            'phases.*.budget.*.rate_duration' => 'nullable|numeric|min:0',
            'phases.*.budget.*.rate_increase' => 'nullable|numeric|min:0',
            'phases.*.budget.*.this_phase' => 'nullable|numeric|min:0',
            'phases.*.budget.*.next_phase' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'phases.array' => 'Phases must be an array.',
            'phases.*.amount_sanctioned.numeric' => 'Amount sanctioned must be a number.',
            'phases.*.amount_sanctioned.min' => 'Amount sanctioned cannot be negative.',
            'phases.*.budget.array' => 'Budget must be an array.',
            'phases.*.budget.*.particular.string' => 'Particular must be a string.',
            'phases.*.budget.*.particular.max' => 'Particular cannot exceed 255 characters.',
            'phases.*.budget.*.rate_quantity.numeric' => 'Rate quantity must be a number.',
            'phases.*.budget.*.rate_multiplier.numeric' => 'Rate multiplier must be a number.',
            'phases.*.budget.*.rate_duration.numeric' => 'Rate duration must be a number.',
            'phases.*.budget.*.rate_increase.numeric' => 'Rate increase must be a number.',
            'phases.*.budget.*.this_phase.numeric' => 'This phase amount must be a number.',
            'phases.*.budget.*.next_phase.numeric' => 'Next phase amount must be a number.',
        ];
    }
}

