<?php

namespace App\Http\Requests\Projects\CCI;

use Illuminate\Foundation\Http\FormRequest;

class StoreCCIPresentSituationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'internal_challenges' => 'nullable|string',
            'external_challenges' => 'nullable|string',
            'area_of_focus' => 'nullable|string',
        ];
    }
}

