<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class ApproveProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'coordinator';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'commencement_month' => 'required|integer|min:1|max:12',
            'commencement_year' => 'required|integer|min:2000|max:2100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'commencement_month.required' => 'Commencement month is required.',
            'commencement_month.integer' => 'Commencement month must be a valid month.',
            'commencement_month.min' => 'Commencement month must be between 1 and 12.',
            'commencement_month.max' => 'Commencement month must be between 1 and 12.',
            'commencement_year.required' => 'Commencement year is required.',
            'commencement_year.integer' => 'Commencement year must be a valid year.',
            'commencement_year.min' => 'Commencement year must be a valid year.',
            'commencement_year.max' => 'Commencement year cannot be more than 10 years in the future.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $month = $this->input('commencement_month');
            $year = $this->input('commencement_year');

            if ($month && $year) {
                try {
                    $commencementDate = Carbon::create($year, $month, 1)->startOfMonth();
                    $currentDate = Carbon::now()->startOfMonth();

                    if ($commencementDate->isBefore($currentDate)) {
                        $validator->errors()->add(
                            'commencement_date',
                            'Commencement Month & Year cannot be before the current month and year. Please update it to present or future month and year before approving.'
                        );
                    }
                } catch (\Exception $e) {
                    $validator->errors()->add(
                        'commencement_date',
                        'Invalid commencement date. Please check the month and year values.'
                    );
                }
            }
        });
    }
}
