<?php

namespace App\Http\Requests\Projects\ILP;

use Illuminate\Foundation\Http\FormRequest;

class StoreILPStrengthWeaknessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'strengths' => 'nullable|array',
            'weaknesses' => 'nullable|array',
        ];
    }
}

