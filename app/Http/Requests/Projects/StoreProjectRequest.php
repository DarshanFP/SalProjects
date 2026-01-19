<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow authenticated users to create projects
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $isDraft = $this->has('save_as_draft') && $this->input('save_as_draft') == '1';
        
        return [
            'project_type' => $isDraft ? 'nullable|string|max:255' : 'required|string|max:255',
            'project_title' => 'nullable|string|max:255',
            'society_name' => 'nullable|string|max:255',
            'president_name' => 'nullable|string|max:255',
            'in_charge' => 'nullable|integer|exists:users,id',
            'in_charge_name' => 'nullable|string|max:255',
            'in_charge_mobile' => 'nullable|string|max:255',
            'in_charge_email' => 'nullable|string|max:255',
            'executor_name' => 'nullable|string|max:255',
            'executor_mobile' => 'nullable|string|max:255',
            'executor_email' => 'nullable|string|max:255',
            'full_address' => 'nullable|string|max:255',
            'overall_project_period' => 'nullable|integer|min:1|max:4',
            'current_phase' => 'nullable|integer|min:1',
            'commencement_month' => 'nullable|integer|min:1|max:12',
            'commencement_year' => 'nullable|integer|min:2000|max:2100',
            'overall_project_budget' => 'nullable|numeric|min:0',
            // Note: amount_forwarded validation includes check against overall_project_budget
            'coordinator_india' => 'nullable|integer|exists:users,id',
            'coordinator_india_name' => 'nullable|string|max:255',
            'coordinator_india_phone' => 'nullable|string|max:255',
            'coordinator_india_email' => 'nullable|email|max:255',
            'coordinator_luzern' => 'nullable|integer|exists:users,id',
            'coordinator_luzern_name' => 'nullable|string|max:255',
            'coordinator_luzern_phone' => 'nullable|string|max:255',
            'coordinator_luzern_email' => 'nullable|email|max:255',
            'initial_information' => 'nullable|string',
            'target_beneficiaries' => 'nullable|string',
            'general_situation' => 'nullable|string',
            'need_of_project' => 'nullable|string',
            'goal' => 'nullable|string',
            'total_amount_sanctioned' => 'nullable|numeric|min:0',
            'amount_forwarded' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    $overallBudget = $this->input('overall_project_budget', 0);
                    $localContribution = (float) $this->input('local_contribution', 0);
                    $combined = ((float) $value) + $localContribution;
                    if ($combined > 0 && $overallBudget > 0 && $combined > $overallBudget) {
                        $fail('The sum of Amount Forwarded and Local Contribution cannot exceed the overall project budget (Rs. ' . number_format($overallBudget, 2) . ').');
                    }
                },
            ],
            'local_contribution' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'predecessor_project' => 'nullable|string|exists:projects,project_id',
            'save_as_draft' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'project_type.required' => 'Project type is required.',
            'project_type.string' => 'Project type must be a valid string.',
            'in_charge.exists' => 'Selected in-charge user does not exist.',
            'overall_project_period.integer' => 'Overall project period must be a number.',
            'overall_project_period.min' => 'Overall project period must be at least 1 year.',
            'overall_project_period.max' => 'Overall project period cannot exceed 4 years.',
            'current_phase.integer' => 'Current phase must be a number.',
            'current_phase.min' => 'Current phase must be at least 1.',
            'commencement_month.integer' => 'Commencement month must be a number.',
            'commencement_month.min' => 'Commencement month must be between 1 and 12.',
            'commencement_month.max' => 'Commencement month must be between 1 and 12.',
            'commencement_year.integer' => 'Commencement year must be a number.',
            'commencement_year.min' => 'Commencement year must be a valid year.',
            'commencement_year.max' => 'Commencement year must be a valid year.',
            'overall_project_budget.numeric' => 'Overall project budget must be a number.',
            'overall_project_budget.min' => 'Overall project budget cannot be negative.',
            'coordinator_india.exists' => 'Selected coordinator (India) does not exist.',
            'coordinator_luzern.exists' => 'Selected coordinator (Luzern) does not exist.',
            'coordinator_india_email.email' => 'Coordinator India email must be a valid email address.',
            'coordinator_luzern_email.email' => 'Coordinator Luzern email must be a valid email address.',
            'total_amount_sanctioned.numeric' => 'Total amount sanctioned must be a number.',
            'total_amount_sanctioned.min' => 'Total amount sanctioned cannot be negative.',
            'amount_forwarded.numeric' => 'Amount forwarded must be a number.',
            'amount_forwarded.min' => 'Amount forwarded cannot be negative.',
            'predecessor_project.exists' => 'Selected predecessor project does not exist.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert save_as_draft to boolean if present
        if ($this->has('save_as_draft')) {
            $this->merge([
                'save_as_draft' => filter_var($this->save_as_draft, FILTER_VALIDATE_BOOLEAN)
            ]);
        }
    }
}

