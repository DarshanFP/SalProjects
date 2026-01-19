<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectEduRUTBasicInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'institution_type' => 'nullable|string|max:255',
            'group_type' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'project_location' => 'nullable|string|max:255',
            'sisters_work' => 'nullable|string',
            'conditions' => 'nullable|string',
            'problems' => 'nullable|string',
            'need' => 'nullable|string',
            'criteria' => 'nullable|string',
        ];
    }
}

