<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;

class StoreEduRUTTargetGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'target_group' => 'array',
            'target_group.*.beneficiary_name' => 'nullable|string|max:255',
            'target_group.*.caste' => 'nullable|string|max:255',
            'target_group.*.institution_name' => 'nullable|string|max:255',
            'target_group.*.class_standard' => 'nullable|string|max:255',
            'target_group.*.total_tuition_fee' => 'nullable|numeric|min:0',
            'target_group.*.eligibility_scholarship' => 'nullable|boolean',
            'target_group.*.expected_amount' => 'nullable|numeric|min:0',
            'target_group.*.contribution_from_family' => 'nullable|numeric|min:0',
        ];
    }
}

