<?php

namespace App\Http\Requests\Projects\IGE;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateIGEOngoingBeneficiariesRequest extends FormRequest
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
            'obeneficiary_name' => 'array',
            'obeneficiary_name.*' => 'nullable|string|max:255',
            'ocaste' => 'array',
            'ocaste.*' => 'nullable|string|max:255',
            'oaddress' => 'array',
            'oaddress.*' => 'nullable|string|max:500',
            'ocurrent_group_year_of_study' => 'array',
            'ocurrent_group_year_of_study.*' => 'nullable|string|max:255',
            'operformance_details' => 'array',
            'operformance_details.*' => 'nullable|string|max:500',
        ];
    }
}

