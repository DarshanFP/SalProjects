<?php

namespace App\Http\Requests\Projects\RST;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateRSTInstitutionInfoRequest extends FormRequest
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
            'year_setup' => 'nullable|string|max:255',
            'total_students_trained' => 'nullable|integer|min:0',
            'beneficiaries_last_year' => 'nullable|integer|min:0',
            'training_outcome' => 'nullable|string',
        ];
    }
}

