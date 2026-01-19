<?php

namespace App\Http\Requests\Projects\IES;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateIESExpensesRequest extends FormRequest
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
            'total_expenses' => 'nullable|numeric|min:0',
            'expected_scholarship_govt' => 'nullable|numeric|min:0',
            'support_other_sources' => 'nullable|numeric|min:0',
            'beneficiary_contribution' => 'nullable|numeric|min:0',
            'balance_requested' => 'nullable|numeric|min:0',
            'particulars' => 'array',
            'particulars.*' => 'nullable|string|max:255',
            'amounts' => 'array',
            'amounts.*' => 'nullable|numeric|min:0',
        ];
    }
}

