<?php

namespace App\Http\Requests\Projects\IGE;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateIGENewBeneficiariesRequest extends FormRequest
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
            'beneficiary_name' => 'array',
            'beneficiary_name.*' => 'nullable|string|max:255',
            'caste' => 'array',
            'caste.*' => 'nullable|string|max:255',
            'address' => 'array',
            'address.*' => 'nullable|string|max:500',
            'group_year_of_study' => 'array',
            'group_year_of_study.*' => 'nullable|string|max:255',
            'family_background_need' => 'array',
            'family_background_need.*' => 'nullable|string|max:500',
        ];
    }
}

