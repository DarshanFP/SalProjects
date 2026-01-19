<?php

namespace App\Http\Requests\Projects\IIES;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateIIESImmediateFamilyDetailsRequest extends FormRequest
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
            'iies_mother_expired'        => 'nullable|boolean',
            'iies_father_expired'        => 'nullable|boolean',
            'iies_grandmother_support'   => 'nullable|boolean',
            'iies_grandfather_support'   => 'nullable|boolean',
            'iies_father_deserted'       => 'nullable|boolean',
            'iies_family_details_others' => 'nullable|string|max:255',
            'iies_father_sick'           => 'nullable|boolean',
            'iies_father_hiv_aids'       => 'nullable|boolean',
            'iies_father_disabled'       => 'nullable|boolean',
            'iies_father_alcoholic'      => 'nullable|boolean',
            'iies_father_health_others'  => 'nullable|string|max:255',
            'iies_mother_sick'           => 'nullable|boolean',
            'iies_mother_hiv_aids'       => 'nullable|boolean',
            'iies_mother_disabled'       => 'nullable|boolean',
            'iies_mother_alcoholic'      => 'nullable|boolean',
            'iies_mother_health_others'  => 'nullable|string|max:255',
            'iies_own_house'             => 'nullable|boolean',
            'iies_rented_house'          => 'nullable|boolean',
            'iies_residential_others'    => 'nullable|string|max:255',
            'iies_family_situation'      => 'nullable|string',
            'iies_assistance_need'       => 'nullable|string',
            'iies_received_support'      => 'nullable|boolean',
            'iies_support_details'       => 'nullable|string',
            'iies_employed_with_stanns'  => 'nullable|boolean',
            'iies_employment_details'    => 'nullable|string',
        ];
    }
}

