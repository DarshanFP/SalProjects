<?php

namespace App\Http\Requests\Projects\IES;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateIESFamilyWorkingMembersRequest extends FormRequest
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
            'member_name' => 'array',
            'member_name.*' => 'nullable|string|max:255',
            'work_nature' => 'array',
            'work_nature.*' => 'nullable|string|max:255',
            'monthly_income' => 'array',
            'monthly_income.*' => 'nullable|numeric|min:0',
        ];
    }
}

