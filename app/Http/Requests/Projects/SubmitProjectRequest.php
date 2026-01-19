<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Constants\ProjectStatus;
use App\Helpers\ProjectPermissionHelper;

class SubmitProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $projectId = $this->route('project_id');
        $project = Project::where('project_id', $projectId)->first();
        
        if (!$project) {
            return false;
        }

        $user = Auth::user();
        
        // Use ProjectPermissionHelper for consistent permission checking
        return ProjectPermissionHelper::canSubmit($project, $user);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // No additional validation needed for submission
            // The authorization check ensures project is in correct status
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Custom messages if needed
        ];
    }
}

