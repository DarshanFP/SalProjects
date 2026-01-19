<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'file' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'file_name' => 'nullable|string|max:255',
            'attachment_description' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'file.file' => 'The uploaded file must be a valid file.',
            'file.mimes' => 'Only PDF, DOC, and DOCX files are allowed.',
            'file.max' => 'The file size must not exceed 2MB.',
        ];
    }
}

