<?php

namespace App\Http\Requests\Projects\IAH;

use Illuminate\Foundation\Http\FormRequest;

class StoreIAHBudgetDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'particular' => 'array',
            'particular.*' => 'nullable|string|max:255',
            'amount' => 'array',
            'amount.*' => 'nullable|numeric|min:0',
            'family_contribution' => 'nullable|numeric|min:0',
        ];
    }
}

