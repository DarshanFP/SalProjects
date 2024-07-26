<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\Project;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
        DB::beginTransaction();
        try {
            Log::info('ProjectController@store - Data received from form', $request->all());

            $project = (new GeneralInfoController())->store($request);
            (new KeyInformationController())->store($request, $project);
            (new BudgetController())->store($request, $project);
            (new AttachmentController())->store($request, $project);

            DB::commit();

            return redirect()->route('projects.index')->with('success', 'Project created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProjectController@store - Error', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors(['error' => 'There was an error creating the project. Please try again.'])->withInput();
        }
    }

    public function show($project_id)
    {
        $project = Project::where('project_id', $project_id)->with('budgets', 'attachments')->firstOrFail();
        $user = Auth::user();
        return view('projects.Oldprojects.show', compact('project', 'user'));
    }

    public function edit($project_id)
    {
        $project = Project::where('project_id', $project_id)->with('budgets', 'attachments')->firstOrFail();
        $users = User::all();
        $user = Auth::user();
        return view('projects.Oldprojects.edit', compact('project', 'users', 'user'));
    }

    public function update(Request $request, $project_id)
    {
        DB::beginTransaction();
        try {
            Log::info('ProjectController@update - Data received from form', $request->all());

            $project = (new GeneralInfoController())->update($request, $project_id);
            (new KeyInformationController())->update($request, $project);
            (new BudgetController())->update($request, $project);
            (new AttachmentController())->update($request, $project);

            DB::commit();

            return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProjectController@update - Error', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors(['error' => 'There was an error updating the project. Please try again.'])->withInput();
        }
    }

    public function destroy($project_id)
    {
        DB::beginTransaction();
        try {
            $project = Project::where('project_id', $project_id)->firstOrFail();
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
