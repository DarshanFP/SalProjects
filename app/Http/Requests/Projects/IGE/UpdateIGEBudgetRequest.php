<?php

namespace App\Http\Requests\Projects\IGE;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateIGEBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $projectId = $this->route('projectId') ?? $this->input('project_id');
        
        if (!$projectId) {
            return false;
        }
        
        $project = Project::where('project_id', $projectId)->first();
        
        if (!$project) {
            return false;
        }

        return ProjectPermissionHelper::canEdit($project, Auth::user());
    }

    public function rules(): array
    {
        return [
            'name.*' => 'nullable|string|max:255',
            'study_proposed.*' => 'nullable|string|max:255',
            'college_fees.*' => 'nullable|numeric|min:0',
            'hostel_fees.*' => 'nullable|numeric|min:0',
            'total_amount.*' => 'nullable|numeric|min:0',
            'scholarship_eligibility.*' => 'nullable|numeric|min:0',
            'family_contribution.*' => 'nullable|numeric|min:0',
            'amount_requested.*' => 'nullable|numeric|min:0',
        ];
    }
}

