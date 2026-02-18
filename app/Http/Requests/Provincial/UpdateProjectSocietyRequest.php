<?php

namespace App\Http\Requests\Provincial;

use App\Helpers\ProjectPermissionHelper;
use App\Models\OldProjects\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateProjectSocietyRequest extends FormRequest
{
    /**
     * Authorize: project must exist and user must be allowed to edit (ProjectPermissionHelper::canEdit).
     * No status bypass; no duplication of editable logic.
     */
    public function authorize(): bool
    {
        $projectId = $this->route('project_id');
        $project = Project::where('project_id', $projectId)->first();

        if (!$project) {
            return false;
        }

        return ProjectPermissionHelper::canEdit($project, Auth::user());
    }

    /**
     * Validation rules for society update.
     */
    public function rules(): array
    {
        return [
            'society_id' => ['required', 'exists:societies,id'],
        ];
    }
}
