<?php

namespace App\Http\Requests\Projects\CCI;

use Illuminate\Foundation\Http\FormRequest;

class StoreCCIPersonalSituationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'children_with_parents_last_year' => 'nullable|integer|min:0',
            'children_with_parents_current_year' => 'nullable|integer|min:0',
            'semi_orphans_last_year' => 'nullable|integer|min:0',
            'semi_orphans_current_year' => 'nullable|integer|min:0',
            'orphans_last_year' => 'nullable|integer|min:0',
            'orphans_current_year' => 'nullable|integer|min:0',
            'hiv_infected_last_year' => 'nullable|integer|min:0',
            'hiv_infected_current_year' => 'nullable|integer|min:0',
            'differently_abled_last_year' => 'nullable|integer|min:0',
            'differently_abled_current_year' => 'nullable|integer|min:0',
            'parents_in_conflict_last_year' => 'nullable|integer|min:0',
            'parents_in_conflict_current_year' => 'nullable|integer|min:0',
            'other_ailments_last_year' => 'nullable|integer|min:0',
            'other_ailments_current_year' => 'nullable|integer|min:0',
            'general_remarks' => 'nullable|string',
        ];
    }
}

