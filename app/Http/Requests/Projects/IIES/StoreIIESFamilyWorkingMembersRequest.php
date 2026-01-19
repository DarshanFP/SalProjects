<?php

namespace App\Http\Requests\Projects\IIES;

use Illuminate\Foundation\Http\FormRequest;

class StoreIIESFamilyWorkingMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'iies_member_name' => 'array',
            'iies_member_name.*' => 'nullable|string|max:255',
            'iies_work_nature' => 'array',
            'iies_work_nature.*' => 'nullable|string|max:255',
            'iies_monthly_income' => 'array',
            'iies_monthly_income.*' => 'nullable|numeric|min:0',
        ];
    }
}

