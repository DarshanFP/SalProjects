<?php

namespace App\Http\Requests\Projects\ILP;

use Illuminate\Foundation\Http\FormRequest;

class StoreILPAttachedDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'attachments.aadhar_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'attachments.request_letter_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'attachments.purchase_quotation_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'attachments.other_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ];
    }
}

