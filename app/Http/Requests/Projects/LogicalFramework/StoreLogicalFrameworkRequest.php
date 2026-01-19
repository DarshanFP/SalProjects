<?php

namespace App\Http\Requests\Projects\LogicalFramework;

use Illuminate\Foundation\Http\FormRequest;

class StoreLogicalFrameworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'project_id' => 'required|string',
            'objectives' => 'array',
            'objectives.*.objective' => 'nullable|string',
            'objectives.*.results' => 'array',
            'objectives.*.results.*.result' => 'nullable|string',
            'objectives.*.risks' => 'array',
            'objectives.*.risks.*.risk' => 'nullable|string',
            'objectives.*.activities' => 'array',
            'objectives.*.activities.*.activity' => 'nullable|string',
            'objectives.*.activities.*.verification' => 'nullable|string',
            'objectives.*.activities.*.timeframe.months' => 'array',
            'objectives.*.activities.*.timeframe.months.*' => 'nullable|boolean',
        ];
    }
}

