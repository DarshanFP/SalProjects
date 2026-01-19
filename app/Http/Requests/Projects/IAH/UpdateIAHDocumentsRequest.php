<?php

namespace App\Http\Requests\Projects\IAH;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateIAHDocumentsRequest extends FormRequest
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
            'aadhar_copy'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'request_letter'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'medical_reports' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'other_docs'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ];
    }
}

