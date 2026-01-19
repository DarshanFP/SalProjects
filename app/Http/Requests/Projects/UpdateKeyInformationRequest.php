<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateKeyInformationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get project from route or request
        $project = $this->route('project');
        
        if (!$project instanceof Project) {
            return false;
        }

        $user = Auth::user();
        
        // Use ProjectPermissionHelper for consistent permission checking
        return ProjectPermissionHelper::canEdit($project, $user);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'goal' => 'required|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'goal.required' => 'Goal is required.',
            'goal.string' => 'Goal must be a string.',
        ];
    }
}

