<?php

namespace App\Http\Requests\Projects\RST;

use Illuminate\Foundation\Http\FormRequest;

class StoreRSTTargetGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'tg_no_of_beneficiaries' => 'nullable|integer|min:0',
            'beneficiaries_description_problems' => 'nullable|string',
        ];
    }
}

