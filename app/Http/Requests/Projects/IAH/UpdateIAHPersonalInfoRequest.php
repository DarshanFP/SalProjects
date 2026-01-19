<?php

namespace App\Http\Requests\Projects\IAH;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateIAHPersonalInfoRequest extends FormRequest
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
            'name' => 'nullable|string|max:255',
            'age' => 'nullable|integer|min:0|max:150',
            'gender' => 'nullable|string|max:255',
            'dob' => 'nullable|date',
            'aadhar' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'guardian_name' => 'nullable|string|max:255',
            'children' => 'nullable|string|max:255',
            'caste' => 'nullable|string|max:255',
            'religion' => 'nullable|string|max:255',
        ];
    }
}

