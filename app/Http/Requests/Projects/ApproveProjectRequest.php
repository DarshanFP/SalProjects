<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ApproveProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $ok = auth()->check() && in_array(auth()->user()->role, ['coordinator', 'general']);
        if (!$ok) {
            Log::warning('ApproveProjectRequest: authorize failed', [
                'user_id' => auth()->id(),
                'role' => auth()->user()?->role,
                'project_id' => $this->route('project_id'),
            ]);
        }
        return $ok;
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
                        Log::warning('ApproveProjectRequest: commencement date in the past', [
                            'project_id' => $this->route('project_id'),
                            'commencement_month' => $month,
                            'commencement_year' => $year,
                        ]);
                        $validator->errors()->add(
                            'commencement_date',
                            'Commencement Month & Year cannot be before the current month and year. Please update it to present or future month and year before approving.'
                        );
                    }
                } catch (\Exception $e) {
                    Log::warning('ApproveProjectRequest: invalid commencement date', [
                        'project_id' => $this->route('project_id'),
                        'month' => $month,
                        'year' => $year,
                        'exception' => $e->getMessage(),
                    ]);
                    $validator->errors()->add(
                        'commencement_date',
                        'Invalid commencement date. Please check the month and year values.'
                    );
                }
            }
        });
    }

    /**
     * Handle a failed validation attempt (log before redirect).
     */
    protected function failedValidation(Validator $validator)
    {
        Log::warning('ApproveProjectRequest: validation failed', [
            'project_id' => $this->route('project_id'),
            'errors' => $validator->errors()->toArray(),
        ]);
        parent::failedValidation($validator);
    }
}
