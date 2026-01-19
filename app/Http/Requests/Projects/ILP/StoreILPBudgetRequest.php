<?php

namespace App\Http\Requests\Projects\ILP;

use Illuminate\Foundation\Http\FormRequest;

class StoreILPBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'budget_desc' => 'array',
            'budget_desc.*' => 'nullable|string|max:255',
            'cost' => 'array',
            'cost.*' => 'nullable|numeric|min:0',
            'beneficiary_contribution' => 'nullable|numeric|min:0',
            'amount_requested' => 'nullable|numeric|min:0',
        ];
    }
}

