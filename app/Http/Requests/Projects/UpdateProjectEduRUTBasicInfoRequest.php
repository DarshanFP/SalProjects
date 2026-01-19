<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateProjectEduRUTBasicInfoRequest extends FormRequest
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
            'institution_type' => 'nullable|string|max:255',
            'group_type' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'project_location' => 'nullable|string|max:255',
            'sisters_work' => 'nullable|string',
            'conditions' => 'nullable|string',
            'problems' => 'nullable|string',
            'need' => 'nullable|string',
            'criteria' => 'nullable|string',
        ];
    }
}

