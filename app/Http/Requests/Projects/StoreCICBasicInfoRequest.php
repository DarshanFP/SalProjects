<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;

class StoreCICBasicInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'number_served_since_inception' => 'nullable|integer|min:0',
            'number_served_previous_year' => 'nullable|integer|min:0',
            'beneficiary_categories' => 'nullable|string',
            'sisters_intervention' => 'nullable|string',
            'beneficiary_conditions' => 'nullable|string',
            'beneficiary_problems' => 'nullable|string',
            'institution_challenges' => 'nullable|string',
            'support_received' => 'nullable|string',
            'project_need' => 'nullable|string',
        ];
    }
}

