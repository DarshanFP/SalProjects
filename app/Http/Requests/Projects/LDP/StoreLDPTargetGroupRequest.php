<?php

namespace App\Http\Requests\Projects\LDP;

use Illuminate\Foundation\Http\FormRequest;

class StoreLDPTargetGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'L_beneficiary_name.*' => 'nullable|string|max:255',
            'L_family_situation.*' => 'nullable|string|max:500',
            'L_nature_of_livelihood.*' => 'nullable|string|max:500',
            'L_amount_requested.*' => 'nullable|numeric|min:0',
        ];
    }
}

