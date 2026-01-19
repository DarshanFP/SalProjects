<?php

namespace App\Http\Requests\Projects\LDP;

use Illuminate\Foundation\Http\FormRequest;

class StoreLDPInterventionLogicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'intervention_description' => 'nullable|string',
        ];
    }
}

