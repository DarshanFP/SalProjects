<?php

namespace App\Http\Requests\Projects\IES;

use Illuminate\Foundation\Http\FormRequest;

class StoreIESAttachmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'aadhar_card' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'fee_quotation' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'scholarship_proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'medical_confirmation' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'caste_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'self_declaration' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'death_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'request_letter' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ];
    }
}

