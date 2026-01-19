<?php

namespace App\Http\Requests\Projects\IGE;

use Illuminate\Foundation\Http\FormRequest;

class StoreIGEBeneficiariesSupportedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'class' => 'array',
            'class.*' => 'nullable|string|max:255',
            'total_number' => 'array',
            'total_number.*' => 'nullable|integer|min:0',
        ];
    }
}

