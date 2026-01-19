<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;

class StoreEduRUTAnnexedTargetGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'project_id' => 'required|string',
            'annexed_target_group' => 'array',
            'annexed_target_group.*.beneficiary_name' => 'nullable|string|max:255',
            'annexed_target_group.*.family_background' => 'nullable|string',
            'annexed_target_group.*.need_of_support' => 'nullable|string',
        ];
    }
}

