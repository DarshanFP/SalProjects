<?php

namespace App\Http\Requests\Projects\IAH;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateIAHBudgetDetailsRequest extends FormRequest
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
            'particular' => 'array',
            'particular.*' => 'nullable|string|max:255',
            'amount' => 'array',
            'amount.*' => 'nullable|numeric|min:0',
            'family_contribution' => 'nullable|numeric|min:0',
        ];
    }
}

