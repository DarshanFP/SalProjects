<?php

namespace App\Http\Requests\Projects\ILP;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateILPAttachedDocumentsRequest extends FormRequest
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
            'attachments.aadhar_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'attachments.request_letter_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'attachments.purchase_quotation_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'attachments.other_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ];
    }
}

