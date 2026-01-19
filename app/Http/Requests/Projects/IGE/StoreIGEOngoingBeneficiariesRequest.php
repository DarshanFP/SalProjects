<?php

namespace App\Http\Requests\Projects\IGE;

use Illuminate\Foundation\Http\FormRequest;

class StoreIGEOngoingBeneficiariesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'obeneficiary_name' => 'array',
            'obeneficiary_name.*' => 'nullable|string|max:255',
            'ocaste' => 'array',
            'ocaste.*' => 'nullable|string|max:255',
            'oaddress' => 'array',
            'oaddress.*' => 'nullable|string|max:500',
            'ocurrent_group_year_of_study' => 'array',
            'ocurrent_group_year_of_study.*' => 'nullable|string|max:255',
            'operformance_details' => 'array',
            'operformance_details.*' => 'nullable|string|max:500',
        ];
    }
}

