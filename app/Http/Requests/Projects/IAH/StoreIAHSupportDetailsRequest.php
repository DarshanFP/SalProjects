<?php

namespace App\Http\Requests\Projects\IAH;

use Illuminate\Foundation\Http\FormRequest;

class StoreIAHSupportDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'employed_at_st_ann' => 'nullable|string|max:255',
            'employment_details' => 'nullable|string',
            'received_support' => 'nullable|string|max:255',
            'support_details' => 'nullable|string',
            'govt_support' => 'nullable|string|max:255',
            'govt_support_nature' => 'nullable|string',
        ];
    }
}

