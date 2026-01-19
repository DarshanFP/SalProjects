<?php

namespace App\Http\Requests\Projects\ILP;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\OldProjects\Project;
use App\Helpers\ProjectPermissionHelper;

class UpdateILPRevenueGoalsRequest extends FormRequest
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
            'business_plan_items' => 'array',
            'business_plan_items.*.item' => 'nullable|string|max:255',
            'business_plan_items.*.year_1' => 'nullable|numeric|min:0',
            'business_plan_items.*.year_2' => 'nullable|numeric|min:0',
            'business_plan_items.*.year_3' => 'nullable|numeric|min:0',
            'business_plan_items.*.year_4' => 'nullable|numeric|min:0',
            'annual_income' => 'array',
            'annual_income.*.description' => 'nullable|string|max:255',
            'annual_income.*.year_1' => 'nullable|numeric|min:0',
            'annual_income.*.year_2' => 'nullable|numeric|min:0',
            'annual_income.*.year_3' => 'nullable|numeric|min:0',
            'annual_income.*.year_4' => 'nullable|numeric|min:0',
            'annual_expenses' => 'array',
            'annual_expenses.*.description' => 'nullable|string|max:255',
            'annual_expenses.*.year_1' => 'nullable|numeric|min:0',
            'annual_expenses.*.year_2' => 'nullable|numeric|min:0',
            'annual_expenses.*.year_3' => 'nullable|numeric|min:0',
            'annual_expenses.*.year_4' => 'nullable|numeric|min:0',
        ];
    }
}

