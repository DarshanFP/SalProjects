<?php

namespace App\Http\Requests\Projects\IAH;

use Illuminate\Foundation\Http\FormRequest;

class StoreIAHEarningMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'member_name' => 'array',
            'member_name.*' => 'nullable|string|max:255',
            'work_type' => 'array',
            'work_type.*' => 'nullable|string|max:255',
            'monthly_income' => 'array',
            'monthly_income.*' => 'nullable|numeric|min:0',
        ];
    }
}

