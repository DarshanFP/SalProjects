<?php

namespace App\Http\Requests\Projects\IES;

use Illuminate\Foundation\Http\FormRequest;

class StoreIESFamilyWorkingMembersRequest extends FormRequest
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
            'work_nature' => 'array',
            'work_nature.*' => 'nullable|string|max:255',
            'monthly_income' => 'array',
            'monthly_income.*' => 'nullable|numeric|min:0',
        ];
    }
}

