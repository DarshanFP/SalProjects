<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Projects\ProjectEduRUTBasicInfoController;
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
    //Edu-RUT
    protected $eduRUTBasicInfoController;
    protected $eduRUTTargetGroupController;
    protected $eduRUTAnnexedTargetGroupController;
    // CIC
    protected $cicBasicInfoController;


    public function __construct(
        LogicalFrameworkController $logicalFrameworkController,
        SustainabilityController $sustainabilityController,
        //Edu-RUT
        ProjectEduRUTBasicInfoController $eduRUTBasicInfoController,
        EduRUTTargetGroupController $eduRUTTargetGroupController,
        EduRUTAnnexedTargetGroupController $eduRUTAnnexedTargetGroupController,
        // CIC
        CICBasicInfoController $cicBasicInfoController

    ) {
        $this->logicalFrameworkController = $logicalFrameworkController;
        $this->sustainabilityController = $sustainabilityController;
        //Edu-RUT
        $this->eduRUTBasicInfoController = $eduRUTBasicInfoController;
        $this->eduRUTTargetGroupController = $eduRUTTargetGroupController;
        $this->eduRUTAnnexedTargetGroupController = $eduRUTAnnexedTargetGroupController;
        // CIC
        $this->cicBasicInfoController = $cicBasicInfoController;

    }

    public function index()
    {
        $projects = Project::all();
        $user = Auth::user();

        // Fetch projects where the user is either the owner or the in-charge
        $projects = Project::where('user_id', $user->id)
                       ->orWhere('in_charge', $user->id)
                       ->get();

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
        // Check for Education Rural-Urban-Tribal project type
        if ($request->project_type == 'Rural-Urban-Tribal') {
            $this->eduRUTBasicInfoController->store($request, $project->project_id);
            $this->eduRUTTargetGroupController->store($request, $project->project_id);
            $this->eduRUTAnnexedTargetGroupController->store($request);
        }
        elseif ($request->project_type == 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER') {
            $this->cicBasicInfoController->store($request, $project->project_id);
        }

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
        $project = Project::where('project_id', $project_id)
                        ->with('budgets', 'attachments', 'objectives', 'sustainabilities')
                        ->firstOrFail();

        $user = Auth::user();

        // Initialize variables to null
        $basicInfo = null;
        $targetGroups = null;
        $annexedTargetGroups = null;
        // $cicBasicInfo = null;

        // Fetch EduRUT related data if the project type is Rural-Urban-Tribal
        if ($project->project_type == 'Rural-Urban-Tribal') {
            $basicInfo = $this->eduRUTBasicInfoController->show($project_id)->getData();
            $targetGroups = $this->eduRUTTargetGroupController->show($project_id)->getData();
            $annexedTargetGroups = $this->eduRUTAnnexedTargetGroupController->show($project_id)->getData();
        }
        elseif ($project->project_type == 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER') {
            $project->load(relations: 'cicBasicInfo');
        }

        return view('projects.Oldprojects.show', compact('project', 'user', 'basicInfo', 'targetGroups', 'annexedTargetGroups'));
    }


    // public function edit($project_id)
    // {
    //     Log::info('ProjectController@edit - Received project_id', ['project_id' => $project_id]);

    //     try {
    //         // Fetch the project details along with related data
    //         $project = Project::where('project_id', $project_id)
    //                         ->with('budgets', 'attachments', 'objectives', 'sustainabilities')
    //                         ->firstOrFail();

    //         // Log the entire project data
    //         Log::info('ProjectController@edit - Retrieved project data', ['project' => $project->toArray()]);

    //         // Log related data specifically
    //         Log::info('ProjectController@edit - Project Budgets', ['budgets' => $project->budgets->toArray()]);
    //         Log::info('ProjectController@edit - Project Attachments', ['attachments' => $project->attachments->toArray()]);
    //         Log::info('ProjectController@edit - Project Objectives', ['objectives' => $project->objectives->toArray()]);
    //         Log::info('ProjectController@edit - Project Sustainabilities', ['sustainabilities' => $project->sustainabilities->toArray()]);

    //         // Fetch all users and the currently authenticated user
    //         $users = User::all();
    //         $user = Auth::user();

    //         // Log user data
    //         // Log::info('ProjectController@edit - Users data', ['users' => $users->toArray()]);
    //         // Log::info('ProjectController@edit - Authenticated user data', ['user' => $user->toArray()]);

    //         // Return the view with the retrieved data
    //         return view('projects.Oldprojects.edit', compact('project', 'users', 'user'));

    //     } catch (\Exception $e) {
    //         Log::error('ProjectController@edit - Error retrieving project data', ['error' => $e->getMessage()]);
    //         return redirect()->back()->withErrors(['error' => 'Unable to retrieve project data.']);
    //     }
    // }

    public function edit($project_id)
    {
        Log::info('ProjectController@edit - Received project_id', ['project_id' => $project_id]);

        try {
            $project = Project::where('project_id', $project_id)
                ->with('budgets', 'attachments', 'objectives', 'sustainabilities')
                ->firstOrFail();

            Log::info('ProjectController@edit - Project type', ['project_type' => $project->project_type]);

            if ($project->project_type == 'Rural-Urban-Tribal') {
                $project->load('eduRUTBasicInfo', 'target_groups', 'annexed_target_groups');
            }
            elseif ($project->project_type == 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER') {
                $project->load('cicBasicInfo');
            }

            $users = User::all();
            $user = Auth::user();

            return view('projects.Oldprojects.edit', compact('project', 'users', 'user'));

        } catch (\Exception $e) {
            Log::error('ProjectController@edit - Error retrieving project data', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors(['error' => 'Unable to retrieve project data.']);
        }
    }



    //     public function update(Request $request, $project_id)
// {
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
//         // Check for Education Rural-Urban-Tribal project type
//         if ($request->project_type == 'Rural-Urban-Tribal') {
//             $this->eduRUTBasicInfoController->update($request, $project->project_id);
//             $this->eduRUTTargetGroupController->update($request, $project->project_id);
//             $this->eduRUTAnnexedTargetGroupController->update($request, $project->project_id);
//         }

//         DB::commit();
//         return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
//     } catch (\Exception $e) {
//         DB::rollBack();
//         Log::error('ProjectController@update - Error', ['project_id' => $project_id, 'error' => $e->getMessage()]);
//         return redirect()->back()->withErrors(['error' => 'There was an error updating the project. Please try again.'])->withInput();
//     }
// }
public function update(Request $request, $project_id)
{
    Log::info('ProjectController@update - Start', ['project_id' => $project_id, 'request_data' => $request->all()]);

    DB::beginTransaction();
    try {
        $project = Project::where('project_id', $project_id)->firstOrFail();

        // Update the project details
        $project = (new GeneralInfoController())->update($request, $project->project_id);

        $keyInformation = (new KeyInformationController())->update($request, $project);
        $budget = (new BudgetController())->update($request, $project);
        $attachments = (new AttachmentController())->update($request, $project->project_id);
        $this->logicalFrameworkController->update($request, $project->project_id);
        $this->sustainabilityController->update($request, $project->project_id);

        if ($project->project_type == 'Rural-Urban-Tribal') {
            $this->eduRUTBasicInfoController->update($request, $project->project_id);
            $this->eduRUTTargetGroupController->update($request, $project->project_id);
            $this->eduRUTAnnexedTargetGroupController->update($request, $project->project_id);
        }
        elseif ($project->project_type == 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER') {
            $this->cicBasicInfoController->update($request, $project->project_id);
        }

        DB::commit();
        return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('ProjectController@update - Error', ['project_id' => $project_id, 'error' => $e->getMessage()]);
        return redirect()->back()->withErrors(['error' => 'There was an error updating the project. Please try again.'])->withInput();
    }
}





    // public function destroy($project_id)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $this->sustainabilityController->destroy($project_id);
    //         $this->logicalFrameworkController->destroy($project_id); // Call the destroy method from LogicalFrameworkController

    //         $project = Project::where('project_id', $project_id)->firstOrFail();
    //         // Check for Education Rural-Urban-Tribal project type
    //         if ($project->project_type == 'Rural-Urban-Tribal') {
    //             $this->eduRUTBasicInfoController->destroy($project_id);
    //             $this->eduRUTTargetGroupController->destroy($project_id);
    //             $this->eduRUTAnnexedTargetGroupController->destroy($project_id);
    //         }
    //         $project->delete();

    //         DB::commit();
    //         return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('ProjectController@destroy - Error', ['error' => $e->getMessage()]);
    //         return redirect()->back()->withErrors(['error' => 'There was an error deleting the project. Please try again.']);
    //     }
    // }
    public function destroy($project_id)
    {
        DB::beginTransaction();
        try {
            $this->sustainabilityController->destroy($project_id);
            $this->logicalFrameworkController->destroy($project_id);

            $project = Project::where('project_id', $project_id)->firstOrFail();

            // Check for Education Rural-Urban-Tribal project type
            if ($project->project_type == 'Rural-Urban-Tribal') {
                $this->eduRUTBasicInfoController->destroy($project_id);
                $this->eduRUTTargetGroupController->destroy($project_id);
                $this->eduRUTAnnexedTargetGroupController->destroy($project_id);
            }
            elseif ($project->project_type == 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER') {
                $this->cicBasicInfoController->destroy($project_id);
            }

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
