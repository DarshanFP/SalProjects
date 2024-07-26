<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\Project;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::all();
        $user = Auth::user();
        return view('projects.Oldprojects.index', compact('projects', 'user'));
    }

    public function create()
    {
        $users = User::all();
        $user = Auth::user();
        return view('projects.Oldprojects.createProjects', compact('users', 'user'));
    }
    public function store(Request $request)
    {
        Log::info('GeneralInfoController@store - Data received from form', $request->all());

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
            'total_amount_sanctioned' => 'required|numeric',
            'total_amount_forwarded' => 'required|numeric',
        ]);

        $project = Project::create([
            'project_type' => $validated['project_type'],
            'project_title' => $validated['project_title'],
            'society_name' => $validated['society_name'],
            'president_name' => $validated['president_name'],
            'in_charge' => $validated['in_charge'],
            'in_charge_name' => $validated['in_charge_name'],
            'in_charge_mobile' => $validated['in_charge_mobile'],
            'in_charge_email' => $validated['in_charge_email'],
            'executor_name' => $validated['executor_name'],
            'executor_mobile' => $validated['executor_mobile'],
            'executor_email' => $validated['executor_email'],
            'full_address' => $validated['full_address'],
            'overall_project_period' => $validated['overall_project_period'],
            'current_phase' => $validated['current_phase'],
            'commencement_month_year' => $validated['commencement_year'] . '-' . $validated['commencement_month'] . '-01',
            'overall_project_budget' => $validated['overall_project_budget'],
            'coordinator_india' => $validated['coordinator_india'],
            'coordinator_india_name' => $validated['coordinator_india_name'],
            'coordinator_india_phone' => $validated['coordinator_india_phone'],
            'coordinator_india_email' => $validated['coordinator_india_email'],
            'coordinator_luzern' => $validated['coordinator_luzern'],
            'coordinator_luzern_name' => $validated['coordinator_luzern_name'],
            'coordinator_luzern_phone' => $validated['coordinator_luzern_phone'],
            'coordinator_luzern_email' => $validated['coordinator_luzern_email'],
            'goal' => $validated['goal'],
            'amount_sanctioned' => $validated['total_amount_sanctioned'],
            'amount_forwarded' => $validated['total_amount_forwarded'],
            'status' => 'underwriting', // Set default status
            'user_id' => Auth::id(),
        ]);

        Log::info('GeneralInfoController@store - Data passed to database', $project->toArray());

        return $project;
    }

    public function show($project_id)
    {
        $project = Project::where('project_id', $project_id)->with('budgets', 'attachments')->firstOrFail();
        $user = Auth::user();
        return view('projects.Oldprojects.show', compact('project', 'user'));
    }

    public function edit($id)
    {
        $project = Project::where('project_id', $id)->with('budgets', 'attachments')->firstOrFail();
        $users = User::all();
        $user = Auth::user();
        return view('projects.Oldprojects.edit', compact('project', 'users', 'user'));
    }


    public function update(Request $request, $id)
    {
        Log::info('GeneralInfoController@update - Data received from form', $request->all());

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
            'total_amount_sanctioned' => 'required|numeric',
            'total_amount_forwarded' => 'required|numeric',
        ]);

        $project = Project::where('project_id', $id)->firstOrFail();
        $project->update([
            'project_type' => $validated['project_type'],
            'project_title' => $validated['project_title'],
            'society_name' => $validated['society_name'],
            'president_name' => $validated['president_name'],
            'in_charge' => $validated['in_charge'],
            'in_charge_name' => $validated['in_charge_name'],
            'in_charge_mobile' => $validated['in_charge_mobile'],
            'in_charge_email' => $validated['in_charge_email'],
            'executor_name' => $validated['executor_name'],
            'executor_mobile' => $validated['executor_mobile'],
            'executor_email' => $validated['executor_email'],
            'full_address' => $validated['full_address'],
            'overall_project_period' => $validated['overall_project_period'],
            'current_phase' => $validated['current_phase'],
            'commencement_month_year' => $validated['commencement_year'] . '-' . $validated['commencement_month'] . '-01',
            'overall_project_budget' => $validated['overall_project_budget'],
            'coordinator_india' => $validated['coordinator_india'],
            'coordinator_india_name' => $validated['coordinator_india_name'],
            'coordinator_india_phone' => $validated['coordinator_india_phone'],
            'coordinator_india_email' => $validated['coordinator_india_email'],
            'coordinator_luzern' => $validated['coordinator_luzern'],
            'coordinator_luzern_name' => $validated['coordinator_luzern_name'],
            'coordinator_luzern_phone' => $validated['coordinator_luzern_phone'],
            'coordinator_luzern_email' => $validated['coordinator_luzern_email'],
            'goal' => $validated['goal'],
            'amount_sanctioned' => $validated['total_amount_sanctioned'],
            'amount_forwarded' => $validated['total_amount_forwarded'],
            'status' => 'underwriting', // Ensure status remains consistent
        ]);

        Log::info('GeneralInfoController@update - Data passed to database', $project->toArray());

        return $project;
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $project = Project::where('project_id', $id)->firstOrFail();
            $project->delete();

            DB::commit();
            return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProjectController@destroy - Error', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors(['error' => 'There was an error deleting the project. Please try again.']);
        }
    }
}
