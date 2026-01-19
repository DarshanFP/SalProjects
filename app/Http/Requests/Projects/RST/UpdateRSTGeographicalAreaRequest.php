<?php

namespace App\Http\Requests\Projects\RST;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateRSTGeographicalAreaRequest extends FormRequest
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
            'mandal' => 'required|array',
            'mandal.*' => 'required|string|max:255',
            'village' => 'required|array',
            'village.*' => 'required|string|max:255',
            'town' => 'required|array',
            'town.*' => 'required|string|max:255',
            'no_of_beneficiaries' => 'required|array',
            'no_of_beneficiaries.*' => 'required|integer|min:0',
        ];
    }
}

