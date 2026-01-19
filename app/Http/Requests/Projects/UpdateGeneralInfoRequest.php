<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateGeneralInfoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $projectId = $this->route('project_id') ?? $this->input('project_id');
        
        if (!$projectId) {
            return false;
        }
        
        $project = Project::where('project_id', $projectId)->first();
        
        if (!$project) {
            return false;
        }

        $user = Auth::user();
        
        // Use ProjectPermissionHelper for consistent permission checking
        return ProjectPermissionHelper::canEdit($project, $user);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'project_type' => 'required|string|max:255',
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
            'overall_project_period' => 'nullable|integer',
            'current_phase' => 'nullable|integer',
            'commencement_month' => 'nullable|integer|min:1|max:12',
            'commencement_year' => 'nullable|integer|min:2000|max:2100',
            'overall_project_budget' => 'nullable|numeric|min:0',
            'amount_forwarded' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    $overallBudget = $this->input('overall_project_budget', 0);
                    if ($value > 0 && $overallBudget > 0 && $value > $overallBudget) {
                        $fail('The amount forwarded cannot exceed the overall project budget (' . \App\Helpers\NumberFormatHelper::formatIndianCurrency($overallBudget, 2) . ').');
                    }
                },
            ],
            'coordinator_india_name' => 'nullable|string|max:255',
            'coordinator_india_phone' => 'nullable|string|max:255',
            'coordinator_india_email' => 'nullable|email|max:255',
            'coordinator_luzern' => 'nullable|integer|exists:users,id',
            'coordinator_luzern_name' => 'nullable|string|max:255',
            'coordinator_luzern_phone' => 'nullable|string|max:255',
            'coordinator_luzern_email' => 'nullable|email|max:255',
            'goal' => 'nullable|string',
            'total_amount_sanctioned' => 'nullable|numeric|min:0',
            'coordinator_india' => 'nullable|integer|exists:users,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'project_type.required' => 'Project type is required.',
            'in_charge.exists' => 'Selected in-charge user does not exist.',
            'coordinator_india.exists' => 'Selected coordinator (India) does not exist.',
            'coordinator_luzern.exists' => 'Selected coordinator (Luzern) does not exist.',
            'coordinator_india_email.email' => 'Coordinator India email must be a valid email address.',
            'coordinator_luzern_email.email' => 'Coordinator Luzern email must be a valid email address.',
        ];
    }
}

