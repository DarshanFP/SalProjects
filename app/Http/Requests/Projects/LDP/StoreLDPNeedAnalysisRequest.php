<?php

namespace App\Http\Requests\Projects\LDP;

use Illuminate\Foundation\Http\FormRequest;

class StoreLDPNeedAnalysisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'need_analysis_file' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
        ];
    }
}

