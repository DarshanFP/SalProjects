<?php

namespace App\Http\Requests\Projects\IIES;

use Illuminate\Foundation\Http\FormRequest;

class StoreIIESPersonalInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'iies_bname' => 'required|string|max:255',
            'iies_age' => 'nullable|integer|min:0|max:150',
            'iies_gender' => 'nullable|string|max:10',
            'iies_dob' => 'nullable|date',
            'iies_email' => 'nullable|email|max:255',
            'iies_contact' => 'nullable|string|max:15',
            'iies_aadhar' => 'nullable|string|max:20',
            'iies_full_address' => 'nullable|string|max:500',
            'iies_father_name' => 'nullable|string|max:255',
            'iies_mother_name' => 'nullable|string|max:255',
            'iies_mother_tongue' => 'nullable|string|max:100',
            'iies_current_studies' => 'nullable|string|max:255',
            'iies_bcaste' => 'nullable|string|max:100',
            'iies_father_occupation' => 'nullable|string|max:255',
            'iies_father_income' => 'nullable|numeric|min:0',
            'iies_mother_occupation' => 'nullable|string|max:255',
            'iies_mother_income' => 'nullable|numeric|min:0',
        ];
    }
}

