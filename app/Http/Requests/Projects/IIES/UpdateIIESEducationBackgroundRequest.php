<?php

namespace App\Http\Requests\Projects\IIES;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateIIESEducationBackgroundRequest extends FormRequest
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
            'prev_education'     => 'nullable|string|max:255',
            'prev_institution'   => 'nullable|string|max:255',
            'prev_insti_address' => 'nullable|string|max:500',
            'prev_marks'         => 'nullable|numeric|min:0|max:100',
            'current_studies'    => 'nullable|string|max:255',
            'curr_institution'   => 'nullable|string|max:255',
            'curr_insti_address' => 'nullable|string|max:500',
            'aspiration'         => 'nullable|string|max:500',
            'long_term_effect'   => 'nullable|string|max:500',
        ];
    }
}

