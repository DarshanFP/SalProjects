<?php

namespace App\Http\Requests\Projects\CCI;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateCCIStatisticsRequest extends FormRequest
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
            'total_children_previous_year' => 'nullable|integer|min:0',
            'total_children_current_year' => 'nullable|integer|min:0',
            'reintegrated_children_previous_year' => 'nullable|integer|min:0',
            'reintegrated_children_current_year' => 'nullable|integer|min:0',
            'shifted_children_previous_year' => 'nullable|integer|min:0',
            'shifted_children_current_year' => 'nullable|integer|min:0',
            'pursuing_higher_studies_previous_year' => 'nullable|integer|min:0',
            'pursuing_higher_studies_current_year' => 'nullable|integer|min:0',
            'settled_children_previous_year' => 'nullable|integer|min:0',
            'settled_children_current_year' => 'nullable|integer|min:0',
            'working_children_previous_year' => 'nullable|integer|min:0',
            'working_children_current_year' => 'nullable|integer|min:0',
            'other_category_previous_year' => 'nullable|integer|min:0',
            'other_category_current_year' => 'nullable|integer|min:0',
        ];
    }
}

