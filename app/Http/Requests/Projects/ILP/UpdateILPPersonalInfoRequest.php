<?php

namespace App\Http\Requests\Projects\ILP;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateILPPersonalInfoRequest extends FormRequest
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
            'email' => 'nullable|email|max:255',
            'contact_no' => 'nullable|string|max:255',
            'aadhar_id' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'occupation' => 'nullable|string|max:255',
            'marital_status' => 'nullable|string|max:255',
            'spouse_name' => 'nullable|string|max:255',
            'children_no' => 'nullable|integer|min:0',
            'children_edu' => 'nullable|string',
            'religion' => 'nullable|string|max:255',
            'caste' => 'nullable|string|max:255',
            'family_situation' => 'nullable|string',
            'small_business_status' => 'nullable|string|max:255',
            'small_business_details' => 'nullable|string',
            'monthly_income' => 'nullable|numeric|min:0',
            'business_plan' => 'nullable|string',
        ];
    }
}

