<?php

namespace App\Http\Requests\Projects\RST;

use Illuminate\Foundation\Http\FormRequest;

class StoreRSTBeneficiariesAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'project_area' => 'array',
            'project_area.*' => 'nullable|string|max:255',
            'category_beneficiary' => 'array',
            'category_beneficiary.*' => 'nullable|string|max:255',
            'direct_beneficiaries' => 'array',
            'direct_beneficiaries.*' => 'nullable|integer|min:0',
            'indirect_beneficiaries' => 'array',
            'indirect_beneficiaries.*' => 'nullable|integer|min:0',
        ];
    }
}

