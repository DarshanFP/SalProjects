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
    protected $logicalFrameworkController;
    protected $sustainabilityController;

    public function __construct(
        LogicalFrameworkController $logicalFrameworkController,
        SustainabilityController $sustainabilityController
    ) {
        $this->logicalFrameworkController = $logicalFrameworkController;
        $this->sustainabilityController = $sustainabilityController;
    }

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

        // Store the main project details first
        $project = (new GeneralInfoController())->store($request);

        // Now pass the $project->project_id to the LogicalFrameworkController
        $keyInformation = (new KeyInformationController())->store($request, $project);
        $budget = (new BudgetController())->store($request, $project);
        $attachments = (new AttachmentController())->store($request, $project);

        // Ensure project_id is passed to LogicalFrameworkController
        $request->merge(['project_id' => $project->project_id]);
        $this->logicalFrameworkController->store($request);

        $this->sustainabilityController->store($request, $project->project_id);

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
        $project = Project::where('project_id', $project_id)->with('budgets', 'attachments', 'objectives', 'sustainabilities')->firstOrFail();
        $user = Auth::user();
        return view('projects.Oldprojects.show', compact('project', 'user'));
    }

    public function edit($project_id)
    {
        Log::info('ProjectController@edit - Received project_id', ['project_id' => $project_id]);

        try {
            // Fetch the project details along with related data
            $project = Project::where('project_id', $project_id)
                            ->with('budgets', 'attachments', 'objectives', 'sustainabilities')
                            ->firstOrFail();

            // Log the entire project data
            Log::info('ProjectController@edit - Retrieved project data', ['project' => $project->toArray()]);

            // Log related data specifically
            Log::info('ProjectController@edit - Project Budgets', ['budgets' => $project->budgets->toArray()]);
            Log::info('ProjectController@edit - Project Attachments', ['attachments' => $project->attachments->toArray()]);
            Log::info('ProjectController@edit - Project Objectives', ['objectives' => $project->objectives->toArray()]);
            Log::info('ProjectController@edit - Project Sustainabilities', ['sustainabilities' => $project->sustainabilities->toArray()]);

            // Fetch all users and the currently authenticated user
            $users = User::all();
            $user = Auth::user();

            // Log user data
            // Log::info('ProjectController@edit - Users data', ['users' => $users->toArray()]);
            // Log::info('ProjectController@edit - Authenticated user data', ['user' => $user->toArray()]);

            // Return the view with the retrieved data
            return view('projects.Oldprojects.edit', compact('project', 'users', 'user'));

        } catch (\Exception $e) {
            Log::error('ProjectController@edit - Error retrieving project data', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors(['error' => 'Unable to retrieve project data.']);
        }
    }

    // public function edit($project_id)
    // {
    //     Log::info('ProjectController@edit - Received project_id', ['project_id' => $project_id]);

    //     $project = Project::where('project_id', $project_id)->with('budgets', 'attachments', 'objectives', 'sustainabilities')->firstOrFail();
    //     $users = User::all();
    //     $user = Auth::user();
    //     return view('projects.Oldprojects.edit', compact('project', 'users', 'user'));
    // }

    public function update(Request $request, $project_id)
{
    Log::info('ProjectController@update - Start', ['project_id' => $project_id, 'request_data' => $request->all()]);

    DB::beginTransaction();
    try {
        // Fetch the project using project_id
        $project = Project::where('project_id', $project_id)->firstOrFail();

        // Update the project details
        $project = (new GeneralInfoController())->update($request, $project->project_id);

        $keyInformation = (new KeyInformationController())->update($request, $project);
        $budget = (new BudgetController())->update($request, $project);
        $attachments = (new AttachmentController())->update($request, $project->project_id);
        $this->logicalFrameworkController->update($request, $project->project_id);
        $this->sustainabilityController->update($request, $project->project_id);

        DB::commit();
        return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('ProjectController@update - Error', ['project_id' => $project_id, 'error' => $e->getMessage()]);
        return redirect()->back()->withErrors(['error' => 'There was an error updating the project. Please try again.'])->withInput();
    }
}


    // public function update(Request $request, $project_id)
    // {
    //     Log::info('Current DB Config:', config('database.connections.mysql'));

    //     Log::info('ProjectController@update - Start', ['project_id' => $project_id, 'request_data' => $request->all()]);

    //     DB::beginTransaction();
    //     try {
    //         $project = Project::where('project_id', $project_id)->firstOrFail();

    //         try {
    //             $project = (new GeneralInfoController())->update($request, $project->project_id);
    //         } catch (\Exception $e) {
    //             Log::error('Error in GeneralInfoController@update', ['project_id' => $project_id, 'error' => $e->getMessage()]);
    //             throw $e;
    //         }

    //         try {
    //             $keyInformation = (new KeyInformationController())->update($request, $project);
    //         } catch (\Exception $e) {
    //             Log::error('Error in KeyInformationController@update', ['project_id' => $project_id, 'error' => $e->getMessage()]);
    //             throw $e;
    //         }

    //         try {
    //             $budget = (new BudgetController())->update($request, $project);
    //         } catch (\Exception $e) {
    //             Log::error('Error in BudgetController@update', ['project_id' => $project_id, 'error' => $e->getMessage()]);
    //             throw $e;
    //         }

    //         try {
    //             $attachments = (new AttachmentController())->update($request, $project->project_id);
    //         } catch (\Exception $e) {
    //             Log::error('Error in AttachmentController@update', ['project_id' => $project_id, 'error' => $e->getMessage()]);
    //             throw $e;
    //         }

    //         try {
    //             $this->logicalFrameworkController->update($request, $project->project_id);
    //         } catch (\Exception $e) {
    //             Log::error('Error in LogicalFrameworkController@update', ['project_id' => $project_id, 'error' => $e->getMessage()]);
    //             throw $e;
    //         }

    //         try {
    //             $this->sustainabilityController->update($request, $project->project_id);
    //         } catch (\Exception $e) {
    //             Log::error('Error in SustainabilityController@update', ['project_id' => $project_id, 'error' => $e->getMessage()]);
    //             throw $e;
    //         }

    //         DB::commit();
    //         return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('ProjectController@update - Final Error', ['project_id' => $project_id, 'error' => $e->getMessage()]);
    //         return redirect()->back()->withErrors(['error' => 'There was an error updating the project. Please try again.'])->withInput();
    //     }
    // }


    // public function update(Request $request, $project_id)
    // {
    //     Log::info('ProjectController@update - Attempting to find project', ['project_id' => $project_id]);
    //     $project = Project::where('project_id', $project_id)->firstOrFail();
    //     Log::info('ProjectController@update - Project found', ['project_id' => $project_id]);

    //     Log::info('ProjectController@update - Start', ['project_id' => $project_id, 'request_data' => $request->all()]);

    //     DB::beginTransaction();
    //     try {
    //         // Fetch the project using project_id
    //         $project = Project::where('project_id', $project_id)->firstOrFail();

    //         // Update the project details
    //         $project = (new GeneralInfoController())->update($request, $project->project_id);

    //         $keyInformation = (new KeyInformationController())->update($request, $project);
    //         $budget = (new BudgetController())->update($request, $project);
    //         $attachments = (new AttachmentController())->update($request, $project->project_id);
    //         $this->logicalFrameworkController->update($request, $project->project_id);
    //         $this->sustainabilityController->update($request, $project->project_id);

    //         DB::commit();
    //         return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('ProjectController@update - Error', ['project_id' => $project_id, 'error' => $e->getMessage()]);
    //         return redirect()->back()->withErrors(['error' => 'There was an error updating the project. Please try again.'])->withInput();
    //     }
    // }
    // public function update(Request $request, $project_id)
    // {
    //     Log::info('ProjectController@update - Start', ['project_id' => $project_id, 'request_data' => $request->all()]);

    //     DB::beginTransaction();
    //     try {
    //         $project = (new GeneralInfoController())->update($request, $project_id);

    //         $keyInformation = (new KeyInformationController())->update($request, $project);
    //         $budget = (new BudgetController())->update($request, $project);
    //         $attachments = (new AttachmentController())->update($request, $project_id);
    //         $this->logicalFrameworkController->update($request, $project_id); // Call the update method from LogicalFrameworkController
    //         $this->sustainabilityController->update($request, $project->project_id);

    //         DB::commit();
    //         return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('ProjectController@update - Error', ['project_id' => $project_id, 'error' => $e->getMessage()]);
    //         return redirect()->back()->withErrors(['error' => 'There was an error updating the project. Please try again.'])->withInput();
    //     }
    // }

    public function destroy($project_id)
    {
        DB::beginTransaction();
        try {
            $this->sustainabilityController->destroy($project_id);
            $this->logicalFrameworkController->destroy($project_id); // Call the destroy method from LogicalFrameworkController

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
