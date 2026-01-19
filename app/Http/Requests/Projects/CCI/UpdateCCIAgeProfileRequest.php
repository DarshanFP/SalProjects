<?php

namespace App\Http\Requests\Projects\CCI;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateCCIAgeProfileRequest extends FormRequest
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
        // Same as Store - all fields nullable
        return [
            'education_below_5_bridge_course_prev_year' => 'nullable|integer|min:0',
            'education_below_5_bridge_course_current_year' => 'nullable|integer|min:0',
            'education_below_5_kindergarten_prev_year' => 'nullable|integer|min:0',
            'education_below_5_kindergarten_current_year' => 'nullable|integer|min:0',
            'education_below_5_other_specify' => 'nullable|string|max:255',
            'education_below_5_other_prev_year' => 'nullable|integer|min:0',
            'education_below_5_other_current_year' => 'nullable|integer|min:0',
            // Add more fields as needed
        ];
    }
}

