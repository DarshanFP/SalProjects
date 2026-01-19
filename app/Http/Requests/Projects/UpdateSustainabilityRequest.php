<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateSustainabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $projectId = $this->route('project_id') ?? $this->route('projectId') ?? $this->input('project_id');
        
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
            'sustainability' => 'nullable|string',
            'monitoring_process' => 'nullable|string',
            'reporting_methodology' => 'nullable|string',
            'evaluation_methodology' => 'nullable|string',
        ];
    }
}

