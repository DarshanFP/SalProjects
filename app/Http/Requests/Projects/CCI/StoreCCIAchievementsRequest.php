<?php

namespace App\Http\Requests\Projects\CCI;

use Illuminate\Foundation\Http\FormRequest;

class StoreCCIAchievementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'academic_achievements' => 'nullable|array',
            'sport_achievements' => 'nullable|array',
            'other_achievements' => 'nullable|array',
        ];
    }
}

