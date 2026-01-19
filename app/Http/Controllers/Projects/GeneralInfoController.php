<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\Project;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Constants\ProjectStatus;

class GeneralInfoController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_type' => 'required|string|max:255',
            'project_title' => 'nullable|string|max:255',
            'society_name' => 'nullable|string|max:255',
            'president_name' => 'nullable|string|max:255',
            'in_charge' => 'nullable|integer|exists:users,id',
            'in_charge_name' => 'nullable|string|max:255',
            'in_charge_mobile' => 'nullable|string|max:255',
            'in_charge_email' => 'nullable|string|max:255',
            'executor_name' => 'nullable|string|max:255',
            'executor_mobile' => 'nullable|string|max:255',
            'executor_email' => 'nullable|string|max:255',
            'full_address' => 'nullable|string|max:255',
            'overall_project_period' => 'nullable|integer',
            'current_phase' => 'nullable|integer',
            'commencement_month' => 'nullable|integer',
            'commencement_year' => 'nullable|integer',
            'overall_project_budget' => 'nullable|numeric',
            'coordinator_india' => 'nullable|integer|exists:users,id',
            'coordinator_india_name' => 'nullable|string|max:255',
            'coordinator_india_phone' => 'nullable|string|max:255',
            'coordinator_india_email' => 'nullable|string|max:255',
            'coordinator_luzern' => 'nullable|integer|exists:users,id',
            'coordinator_luzern_name' => 'nullable|string|max:255',
            'coordinator_luzern_phone' => 'nullable|string|max:255',
            'coordinator_luzern_email' => 'nullable|string|max:255',
            'goal' => 'nullable|string',
            'total_amount_sanctioned' => 'nullable|numeric',
            'amount_forwarded' => 'nullable|numeric',
            'local_contribution' => 'nullable|numeric',
            'predecessor_project' => 'nullable|string|exists:projects,project_id',
        ]);

        $commencement_date = null;
        if (!empty($validated['commencement_year']) && !empty($validated['commencement_month'])) {
            $commencement_date = $validated['commencement_year'] . '-' . str_pad($validated['commencement_month'], 2, '0', STR_PAD_LEFT) . '-01';
        }

        $validated['commencement_month_year'] = $commencement_date;
        $validated['user_id'] = Auth::id();
        $validated['status'] = ProjectStatus::DRAFT;
        $validated['amount_forwarded'] = $validated['amount_forwarded'] ?? 0.00;
        $validated['local_contribution'] = $validated['local_contribution'] ?? 0.00;
        $validated['executor_name'] = $request->input('executor_name', Auth::user()->name);
        $validated['executor_mobile'] = $request->input('executor_mobile', Auth::user()->phone);
        $validated['executor_email'] = $request->input('executor_email', Auth::user()->email);

        // Set default in_charge to current user if not provided (required for draft saves)
        $validated['in_charge'] = $validated['in_charge'] ?? Auth::id();
        // goal is now nullable, so no need to set default
        $validated['overall_project_budget'] = $validated['overall_project_budget'] ?? 0.00; // Ensure default even if not in request

        // Map predecessor_project (form field) to predecessor_project_id (database column)
        if (isset($validated['predecessor_project'])) {
            $validated['predecessor_project_id'] = $validated['predecessor_project'] ?: null;
            unset($validated['predecessor_project']);
        }

        // Log only non-sensitive data
        Log::info('GeneralInfoController@store - Data passed to database', [
            'project_type' => $validated['project_type'] ?? null,
            'project_title' => $validated['project_title'] ?? null,
            'user_id' => $validated['user_id'],
            'status' => $validated['status']
        ]);

        $project = Project::create($validated);

        // Log initial status (draft)
        if ($project && $project->project_id) {
            try {
                \App\Services\ProjectStatusService::logStatusChange(
                    $project,
                    null, // No previous status for new projects
                    ProjectStatus::DRAFT,
                    Auth::user(),
                    'Project created'
                );
            } catch (\Exception $e) {
                // Log error but don't fail project creation
                Log::error('Failed to log initial project status', [
                    'project_id' => $project->project_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $project;
    }
    public function edit($project_id)
    {
        // 1. Fetch the project you want to edit
        $project = Project::where('project_id', $project_id)->firstOrFail();

        // 2. Define the $developmentProjects that you need
        //    The exact query depends on your logic/requirements
        //    Example: all projects of type "Development Projects" or "NEXT PHASE - DEVELOPMENT PROPOSAL"
        $developmentProjects = Project::whereIn('project_type', [
            'Development Projects',
            'NEXT PHASE - DEVELOPMENT PROPOSAL'
        ])->get();

        // 3. Also fetch other data, like $users, $user, etc., as needed
        $users = User::all();        // or whatever your logic is
        $user  = Auth::user();                  // or however you define this

        // 4. Return the view with all the variables
        return view('projects.edit', compact('project', 'developmentProjects', 'users', 'user'));
    }



    public function update(FormRequest $request, $project_id)
{
    // This method is called from ProjectController and receives a FormRequest
    // (e.g. UpdateProjectRequest / UpdateGeneralInfoRequest). Validation has
    // already been performed, so we can safely use validated().
    $validated = $request->validated();

    Log::info('GeneralInfoController@update - Start', [
        'project_id' => $project_id,
        'project_type' => $validated['project_type'] ?? null,
        'project_title' => $validated['project_title'] ?? null,
    ]);

    $commencement_date = null;
    if (!empty($validated['commencement_year']) && !empty($validated['commencement_month'])) {
        $commencement_date = $validated['commencement_year'] . '-' . str_pad($validated['commencement_month'], 2, '0', STR_PAD_LEFT) . '-01';
    }

    $validated['commencement_month_year'] = $commencement_date;
    $validated['amount_forwarded'] = $validated['amount_forwarded'] ?? 0.00;
    $validated['local_contribution'] = $validated['local_contribution'] ?? 0.00;
    $validated['executor_name'] = $request->input('executor_name', Auth::user()->name);
    $validated['executor_mobile'] = $request->input('executor_mobile', Auth::user()->phone);
    $validated['executor_email'] = $request->input('executor_email', Auth::user()->email);

    // goal is now nullable, so we can update it normally
    // If goal is not in validated array, don't update it (preserve existing value)
    if (!array_key_exists('goal', $validated)) {
        unset($validated['goal']);
    }

    // Map predecessor_project (form field) to predecessor_project_id (database column)
    if (isset($validated['predecessor_project'])) {
        $validated['predecessor_project_id'] = $validated['predecessor_project'] ?: null;
        unset($validated['predecessor_project']);
    }

    $project = Project::where('project_id', $project_id)->firstOrFail();
    $project->update($validated);

    // Log only non-sensitive data
    Log::info('GeneralInfoController@update - Data passed to database', [
        'project_id' => $project->project_id,
        'project_type' => $project->project_type,
        'status' => $project->status
    ]);

    return $project;
}

public function show($project_id)
{
    Log::info('GeneralInfoController@show - Start', ['project_id' => $project_id]);

    $project = Project::where('project_id', $project_id)->firstOrFail();

    Log::info('GeneralInfoController@show - Data fetched from database', $project->toArray());

    return $project;
}
public function destroy($project_id)
{
    Log::info('GeneralInfoController@destroy - Start', ['project_id' => $project_id]);

    $project = Project::where('project_id', $project_id)->firstOrFail();
    $project->delete();

    Log::info('GeneralInfoController@destroy - Data deleted from database', $project->toArray());

    return $project;
}
}
