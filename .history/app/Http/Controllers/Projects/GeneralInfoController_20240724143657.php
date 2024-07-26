<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class GeneralInfoController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_type' => 'required|string|max:255',
            'project_title' => 'required|string|max:255',
            'society_name' => 'nullable|string|max:255',
            'president_name' => 'nullable|string|max:255',
            'in_charge' => 'required|integer|exists:users,id',
            'in_charge_name' => 'nullable|string|max:255',
            'in_charge_mobile' => 'nullable|string|max:255',
            'in_charge_email' => 'nullable|string|max:255',
            'executor_name' => 'nullable|string|max:255',
            'executor_mobile' => 'nullable|string|max:255',
            'executor_email' => 'nullable|string|max:255',
            'full_address' => 'nullable|string|max:255',
            'overall_project_period' => 'nullable|integer',
            'current_phase' => 'required|integer',
            'commencement_month' => 'nullable|integer',
            'commencement_year' => 'nullable|integer',
            'overall_project_budget' => 'required|numeric',
            'coordinator_india' => 'nullable|integer|exists:users,id',
            'coordinator_india_name' => 'nullable|string|max:255',
            'coordinator_india_phone' => 'nullable|string|max:255',
            'coordinator_india_email' => 'nullable|string|max:255',
            'coordinator_luzern' => 'nullable|integer|exists:users,id',
            'coordinator_luzern_name' => 'nullable|string|max:255',
            'coordinator_luzern_phone' => 'nullable|string|max:255',
            'coordinator_luzern_email' => 'nullable|string|max:255',
            'goal' => 'required|string',
            'total_amount_sanctioned' => 'nullable|numeric',
            'total_amount_forwarded' => 'nullable|numeric',
            'status' => 'underwriting',
        ]);

        $validated['commencement_month_year'] = $validated['commencement_year'] . '-' . $validated['commencement_month'] . '-01';
        $validated['user_id'] = Auth::id(); // Set the user_id

        Log::info('GeneralInfoController@store - Data passed to database', $validated);

        $project = Project::create($validated);

        return $project;
    }

    public function update(Request $request, $project_id)
    {
        $validated = $request->validate([
            'project_type' => 'required|string|max:255',
            'project_title' => 'required|string|max:255',
            'society_name' => 'nullable|string|max:255',
            'president_name' => 'nullable|string|max:255',
            'in_charge' => 'required|integer|exists:users,id',
            'in_charge_name' => 'nullable|string|max:255',
            'in_charge_mobile' => 'nullable|string|max:255',
            'in_charge_email' => 'nullable|string|max:255',
            'executor_name' => 'nullable|string|max:255',
            'executor_mobile' => 'nullable|string|max:255',
            'executor_email' => 'nullable|string|max:255',
            'full_address' => 'nullable|string|max:255',
            'overall_project_period' => 'nullable|integer',
            'current_phase' => 'required|integer',
            'commencement_month' => 'nullable|integer',
            'commencement_year' => 'nullable|integer',
            'overall_project_budget' => 'required|numeric',
            'coordinator_india' => 'nullable|integer|exists:users,id',
            'coordinator_india_name' => 'nullable|string|max:255',
            'coordinator_india_phone' => 'nullable|string|max:255',
            'coordinator_india_email' => 'nullable|string|max:255',
            'coordinator_luzern' => 'nullable|integer|exists:users,id',
            'coordinator_luzern_name' => 'nullable|string|max:255',
            'coordinator_luzern_phone' => 'nullable|string|max:255',
            'coordinator_luzern_email' => 'nullable|string|max:255',
            'goal' => 'required|string',
            'total_amount_sanctioned' => 'nullable|numeric',
            'total_amount_forwarded' => 'nullable|numeric',
            'status' => 'underwriting',
        ]);

        $validated['commencement_month_year'] = $validated['commencement_year'] . '-' . $validated['commencement_month'] . '-01';

        $project = Project::findOrFail($project_id);
        $project->update($validated);

        Log::info('GeneralInfoController@update - Data passed to database', $project->toArray());

        return $project;
    }
}
