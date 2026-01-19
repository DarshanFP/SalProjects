<?php

namespace App\Http\Requests\Projects\CCI;

use Illuminate\Foundation\Http\FormRequest;

class StoreCCIStatisticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'total_children_previous_year' => 'nullable|integer|min:0',
            'total_children_current_year' => 'nullable|integer|min:0',
            'reintegrated_children_previous_year' => 'nullable|integer|min:0',
            'reintegrated_children_current_year' => 'nullable|integer|min:0',
            'shifted_children_previous_year' => 'nullable|integer|min:0',
            'shifted_children_current_year' => 'nullable|integer|min:0',
            'pursuing_higher_studies_previous_year' => 'nullable|integer|min:0',
            'pursuing_higher_studies_current_year' => 'nullable|integer|min:0',
            'settled_children_previous_year' => 'nullable|integer|min:0',
            'settled_children_current_year' => 'nullable|integer|min:0',
            'working_children_previous_year' => 'nullable|integer|min:0',
            'working_children_current_year' => 'nullable|integer|min:0',
            'other_category_previous_year' => 'nullable|integer|min:0',
            'other_category_current_year' => 'nullable|integer|min:0',
        ];
    }
}

