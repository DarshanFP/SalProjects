<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateEduRUTTargetGroupRequest extends FormRequest
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
            'target_group' => 'array',
            'target_group.*.beneficiary_name' => 'nullable|string|max:255',
            'target_group.*.caste' => 'nullable|string|max:255',
            'target_group.*.institution_name' => 'nullable|string|max:255',
            'target_group.*.class_standard' => 'nullable|string|max:255',
            'target_group.*.total_tuition_fee' => 'nullable|numeric|min:0',
            'target_group.*.eligibility_scholarship' => 'nullable|boolean',
            'target_group.*.expected_amount' => 'nullable|numeric|min:0',
            'target_group.*.contribution_from_family' => 'nullable|numeric|min:0',
        ];
    }
}

