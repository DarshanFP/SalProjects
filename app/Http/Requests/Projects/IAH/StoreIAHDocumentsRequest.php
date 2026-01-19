<?php

namespace App\Http\Requests\Projects\IAH;

use Illuminate\Foundation\Http\FormRequest;

class StoreIAHDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'aadhar_copy'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'request_letter'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'medical_reports' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'other_docs'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ];
    }
}

