<?php

namespace App\Http\Requests\Projects\RST;

use Illuminate\Foundation\Http\FormRequest;

class StoreRSTGeographicalAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'mandal' => 'array',
            'mandal.*' => 'nullable|string|max:255',
            'village' => 'array',
            'village.*' => 'nullable|string|max:255',
            'town' => 'array',
            'town.*' => 'nullable|string|max:255',
            'no_of_beneficiaries' => 'array',
            'no_of_beneficiaries.*' => 'nullable|integer|min:0',
        ];
    }
}

