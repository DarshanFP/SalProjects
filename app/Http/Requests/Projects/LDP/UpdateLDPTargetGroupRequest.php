<?php

namespace App\Http\Requests\Projects\LDP;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateLDPTargetGroupRequest extends FormRequest
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
            'L_beneficiary_name.*' => 'nullable|string|max:255',
            'L_family_situation.*' => 'nullable|string|max:500',
            'L_nature_of_livelihood.*' => 'nullable|string|max:500',
            'L_amount_requested.*' => 'nullable|numeric|min:0',
        ];
    }
}

