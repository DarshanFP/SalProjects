<?php

namespace App\Http\Requests\Projects\IES;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateIESPersonalInfoRequest extends FormRequest
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
            'bname' => 'nullable|string|max:255',
            'age' => 'nullable|integer|min:0|max:150',
            'gender' => 'nullable|string|max:255',
            'dob' => 'nullable|date',
            'email' => 'nullable|email|max:255',
            'contact' => 'nullable|string|max:255',
            'aadhar' => 'nullable|string|max:255',
            'full_address' => 'nullable|string',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'mother_tongue' => 'nullable|string|max:255',
            'current_studies' => 'nullable|string|max:255',
            'bcaste' => 'nullable|string|max:255',
            'father_occupation' => 'nullable|string|max:255',
            'father_income' => 'nullable|numeric|min:0',
            'mother_occupation' => 'nullable|string|max:255',
            'mother_income' => 'nullable|numeric|min:0',
        ];
    }
}

