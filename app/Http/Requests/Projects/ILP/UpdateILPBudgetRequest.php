<?php

namespace App\Http\Requests\Projects\ILP;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateILPBudgetRequest extends FormRequest
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
            'budget_desc' => 'array',
            'budget_desc.*' => 'nullable|string|max:255',
            'cost' => 'array',
            'cost.*' => 'nullable|numeric|min:0',
            'beneficiary_contribution' => 'nullable|numeric|min:0',
            'amount_requested' => 'nullable|numeric|min:0',
        ];
    }
}

