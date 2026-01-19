<?php

namespace App\Http\Requests\Projects\CCI;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateCCIAnnexedTargetGroupRequest extends FormRequest
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
            'annexed_target_group' => 'array',
            'annexed_target_group.*.beneficiary_name' => 'nullable|string|max:255',
            'annexed_target_group.*.dob' => 'nullable|date',
            'annexed_target_group.*.date_of_joining' => 'nullable|date',
            'annexed_target_group.*.class_of_study' => 'nullable|string|max:255',
            'annexed_target_group.*.family_background_description' => 'nullable|string',
        ];
    }
}

