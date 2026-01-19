<?php

namespace App\Http\Requests\Projects\IIES;

use Illuminate\Foundation\Http\FormRequest;

class StoreIIESAttachmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'iies_aadhar_card'          => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'iies_fee_quotation'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'iies_scholarship_proof'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'iies_medical_confirmation' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'iies_caste_certificate'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'iies_self_declaration'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'iies_death_certificate'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'iies_request_letter'       => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ];
    }
}

