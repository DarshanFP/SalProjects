<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $projectId = $this->route('project_id') ?? $this->input('project_id');
        
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
            'file' => 'nullable|file|max:2048', // 2MB max, optional
            'file_name' => 'nullable|string|max:255',
            'attachment_description' => 'nullable|string|max:1000',
        ];
    }
}
