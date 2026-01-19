<?php

namespace App\Http\Requests\Projects\CCI;

use Illuminate\Foundation\Http\FormRequest;

class StoreCCIAnnexedTargetGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'annexed_target_group' => 'array',
            'annexed_target_group.*.beneficiary_name' => 'nullable|string|max:255',
            'annexed_target_group.*.dob' => 'nullable|date',
            'annexed_target_group.*.date_of_joining' => 'nullable|date',
            'annexed_target_group.*.class_of_study' => 'nullable|string|max:255',
            'annexed_target_group.*.family_background_description' => 'nullable|string',
        ];
    }
}

