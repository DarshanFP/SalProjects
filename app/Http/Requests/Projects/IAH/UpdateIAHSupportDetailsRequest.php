<?php

namespace App\Http\Requests\Projects\IAH;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateIAHSupportDetailsRequest extends FormRequest
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
            'employed_at_st_ann' => 'nullable|string|max:255',
            'employment_details' => 'nullable|string',
            'received_support' => 'nullable|string|max:255',
            'support_details' => 'nullable|string',
            'govt_support' => 'nullable|string|max:255',
            'govt_support_nature' => 'nullable|string',
        ];
    }
}

