<?php

namespace App\Http\Requests\Projects\IIES;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateIIESFinancialSupportRequest extends FormRequest
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
            'govt_eligible_scholarship' => 'nullable|string|max:255',
            'scholarship_amt' => 'nullable|numeric|min:0',
            'other_eligible_scholarship' => 'nullable|string|max:255',
            'other_scholarship_amt' => 'nullable|numeric|min:0',
            'family_contrib' => 'nullable|numeric|min:0',
            'no_contrib_reason' => 'nullable|string',
        ];
    }
}

