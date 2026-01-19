<?php

namespace App\Http\Requests\Projects\LogicalFramework;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateLogicalFrameworkRequest extends FormRequest
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
            'objectives' => 'array',
            'objectives.*.objective' => 'nullable|string',
            'objectives.*.results' => 'array',
            'objectives.*.results.*.result' => 'nullable|string',
            'objectives.*.risks' => 'array',
            'objectives.*.risks.*.risk' => 'nullable|string',
            'objectives.*.activities' => 'array',
            'objectives.*.activities.*.activity' => 'nullable|string',
            'objectives.*.activities.*.verification' => 'nullable|string',
            'objectives.*.activities.*.timeframe.months' => 'array',
            'objectives.*.activities.*.timeframe.months.*' => 'nullable|boolean',
        ];
    }
}

