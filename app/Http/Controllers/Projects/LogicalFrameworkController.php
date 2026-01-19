<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\ProjectObjective;
use App\Models\OldProjects\ProjectResult;
use App\Models\OldProjects\ProjectRisk;
use App\Models\OldProjects\ProjectActivity;
use App\Models\OldProjects\ProjectTimeframe;
use DB;
use Illuminate\Support\Facades\Log;

class LogicalFrameworkController extends Controller
{
    // 1. Display a listing of the objectives
    public function index()
    {
        Log::info('Fetching all project objectives.');
        $objectives = ProjectObjective::with(['results', 'risks', 'activities.timeframes'])->get();
        Log::info('Successfully fetched all project objectives.');

        // return view('projects.objectives.index', compact('objectives'));
    }

    // 3. Store a newly created objective in storage
    public function store(Request $request)
    {
        // This controller is orchestrated by ProjectController and receives StoreProjectRequest.
        // We validate the subset we need here (project_id + objectives).
        $validated = $request->validate([
            'project_id' => 'required|string',
            'objectives' => 'nullable|array',
        ]);
        
        Log::info('Incoming request data - logged in LFW', [
            'objectives_count' => count($validated['objectives'] ?? $request->input('objectives', [])),
            'has_activities' => !empty(($validated['objectives'] ?? $request->input('objectives', []))[0]['activities'] ?? []),
        ]);

        $objectives = $validated['objectives'] ?? [];

        // Allow empty objectives - they can be added later during editing
        if (empty($objectives)) {
            Log::info('No objectives data provided - skipping objective storage. Objectives can be added later.');
            return; // Return null to allow the parent controller to continue
        }

        // Check if any objectives have actual data (not all null/empty)
        $hasValidObjectives = false;
        foreach ($objectives as $objectiveData) {
            if (isset($objectiveData['objective']) && !empty(trim($objectiveData['objective']))) {
                $hasValidObjectives = true;
                break;
            }
        }

        if (!$hasValidObjectives) {
            Log::info('No valid objectives data provided (all are null/empty) - skipping objective storage. Objectives can be added later.');
            return; // Return null to allow the parent controller to continue
        }

        DB::transaction(function () use ($validated, $objectives) {
            Log::info('Starting transaction to store new project objective.');

            $projectId = $validated['project_id'];

            foreach ($objectives as $objectiveIndex => $objectiveData) {
                if (isset($objectiveData['objective']) && !empty(trim($objectiveData['objective']))) {
                    Log::info('Creating a new objective for project ID: ' . $projectId, ['objective' => $objectiveData['objective']]);

                    $objective = new ProjectObjective([
                        'objective' => $objectiveData['objective'],
                        'project_id' => $projectId,
                    ]);
                    $objective->save();

                    Log::info('Objective created with ID: ' . $objective->objective_id);

                    // Handle results
                    if (!empty($objectiveData['results'])) {
                        foreach ($objectiveData['results'] as $resultIndex => $resultData) {
                            if (isset($resultData['result'])) {
                                Log::info('Adding result to objective ID: ' . $objective->objective_id, ['result' => $resultData['result']]);

                                $result = new ProjectResult([
                                    'result' => $resultData['result'],
                                    'objective_id' => $objective->objective_id,
                                ]);
                                $result->save();

                                Log::info('Result created with ID: ' . $result->result_id);
                            } else {
                                Log::warning('Result data is missing for objective ID: ' . $objective->objective_id);
                            }
                        }
                    } else {
                        Log::warning('No results data provided for objective ID: ' . $objective->objective_id);
                    }

                    // Handle risks
                    if (!empty($objectiveData['risks'])) {
                        foreach ($objectiveData['risks'] as $riskIndex => $riskData) {
                            if (isset($riskData['risk'])) {
                                Log::info('Adding risk to objective ID: ' . $objective->objective_id, ['risk' => $riskData['risk']]);

                                $risk = new ProjectRisk([
                                    'risk' => $riskData['risk'],
                                    'objective_id' => $objective->objective_id,
                                ]);
                                $risk->save();

                                Log::info('Risk created with ID: ' . $risk->risk_id);
                            } else {
                                Log::warning('Risk data is missing for objective ID: ' . $objective->objective_id);
                            }
                        }
                    } else {
                        Log::warning('No risks data provided for objective ID: ' . $objective->objective_id);
                    }

                    // Handle activities
                    if (!empty($objectiveData['activities'])) {
                        foreach ($objectiveData['activities'] as $activityIndex => $activityData) {
                            if (isset($activityData['activity'])) {
                                Log::info('Adding activity to objective ID: ' . $objective->objective_id, [
                                    'activity' => $activityData['activity'],
                                    'verification' => $activityData['verification']
                                ]);

                                $activity = new ProjectActivity([
                                    'activity' => $activityData['activity'],
                                    'verification' => $activityData['verification'],
                                    'objective_id' => $objective->objective_id,
                                ]);
                                $activity->save();

                                Log::info('Activity created with ID: ' . $activity->activity_id);

                                // Handle timeframes for this activity
                                if (!empty($activityData['timeframe'])) {
                                    foreach ($activityData['timeframe']['months'] as $month => $isActive) {
                                        Log::info('Adding timeframe for activity ID: ' . $activity->activity_id . ' for month: ' . $month, ['is_active' => $isActive]);

                                        $timeframe = new ProjectTimeframe([
                                            'month' => $month,
                                            'is_active' => $isActive,
                                            'activity_id' => $activity->activity_id,
                                        ]);
                                        $timeframe->save();

                                        Log::info('Timeframe created with ID: ' . $timeframe->timeframe_id);
                                    }
                                }
                            } else {
                                Log::warning('Activity data is missing for objective ID: ' . $objective->objective_id);
                            }
                        }
                    } else {
                        Log::warning('No activities data provided for objective ID: ' . $objective->objective_id);
                    }
                } else {
                    Log::warning('Objective data is missing for project ID: ' . $projectId);
                }
            }

            Log::info('Transaction completed successfully for storing new project objective.');
        });

        // return redirect()->route('projects.objectives.index')->with('success', 'Project objectives and related data saved successfully!');
    }

    public function show($project_id)
    {
        Log::info('Fetching objectives and related data for project ID: ' . $project_id);

        $objectives = ProjectObjective::where('project_id', $project_id)
            ->with(['results', 'risks', 'activities.timeframes'])
            ->get();

        Log::info('Successfully fetched objectives for project ID: ' . $project_id);

        // return view('projects.objectives.show', compact('objectives', 'project_id'));
    }

    // 4. Show the form for editing the specified objective
    public function edit($id)
    {
        Log::info('Rendering form to edit objective with ID: ' . $id);

        $objective = ProjectObjective::with(['results', 'risks', 'activities.timeframes'])->findOrFail($id);
        Log::info('Fetched objective for editing with ID: ' . $id);

        // return view('projects.objectives.edit', compact('objective'));
    }

    // 5. Update the specified objective in storage

    public function update(Request $request, $project_id)
{
    // Validate that objectives is an array, but use input() to get all nested data
    // This ensures we get activities, verification, and other nested fields
    $request->validate([
        'objectives' => 'nullable|array',
    ]);

    // Use input() instead of validated() to ensure we get all nested data
    // including activities[][activity], activities[][verification], etc.
    $objectives = $request->input('objectives', []);
    
    DB::transaction(function () use ($objectives, $project_id) {
        Log::info('Starting transaction to update objectives for project ID: ' . $project_id);

        // Delete old objectives, risks, results, and activities
        ProjectObjective::where('project_id', $project_id)->delete();

        foreach ($objectives as $objectiveData) {
            $objective = new ProjectObjective([
                'project_id' => $project_id,
                'objective' => $objectiveData['objective'],
            ]);
            $objective->save();

            foreach ($objectiveData['results'] ?? [] as $resultData) {
                $result = new ProjectResult([
                    'result' => $resultData['result'],
                    'objective_id' => $objective->objective_id,
                ]);
                $result->save();
            }

            foreach ($objectiveData['risks'] ?? [] as $riskData) {
                $risk = new ProjectRisk([
                    'risk' => $riskData['risk'],
                    'objective_id' => $objective->objective_id,
                ]);
                $risk->save();
            }

            foreach ($objectiveData['activities'] ?? [] as $activityData) {
                $activity = new ProjectActivity([
                    'activity' => $activityData['activity'],
                    'verification' => $activityData['verification'],
                    'objective_id' => $objective->objective_id,
                ]);
                $activity->save();

                foreach ($activityData['timeframe']['months'] ?? [] as $month => $isActive) {
                    $timeframe = new ProjectTimeframe([
                        'month' => $month,
                        'is_active' => $isActive,
                        'activity_id' => $activity->activity_id,
                    ]);
                    $timeframe->save();
                }
            }
        }

        Log::info('Transaction completed successfully for updating project objectives.');
    });
}

    // public function update(Request $request, $id)
    // {
    //     DB::transaction(function () use ($request, $id) {
    //         Log::info('Starting transaction to update objective with ID: ' . $id);

    //         $objective = ProjectObjective::findOrFail($id);
    //         $objective->update([
    //             'objective' => $request->input('objective'),
    //         ]);

    //         Log::info('Objective updated with ID: ' . $objective->objective_id);

    //         // Delete existing related data
    //         $objective->results()->delete();
    //         $objective->risks()->delete();
    //         $objective->activities()->delete();

    //         Log::info('Deleted old results, risks, and activities for objective ID: ' . $objective->objective_id);

    //         // Handle results
    //         if (!empty($request->input('results'))) {
    //             foreach ($request->input('results') as $resultData) {
    //                 if (isset($resultData['result'])) {
    //                     Log::info('Adding new result to objective ID: ' . $objective->objective_id);

    //                     $result = new ProjectResult([
    //                         'result' => $resultData['result'],
    //                         'objective_id' => $objective->objective_id,
    //                     ]);
    //                     $result->save();

    //                     Log::info('Result created with ID: ' . $result->result_id);
    //                 } else {
    //                     Log::warning('Result data is missing for objective ID: ' . $objective->objective_id);
    //                 }
    //             }
    //         }

    //         // Handle risks
    //         if (!empty($request->input('risks'))) {
    //             foreach ($request->input('risks') as $riskData) {
    //                 if (isset($riskData['risk'])) {
    //                     Log::info('Adding new risk to objective ID: ' . $objective->objective_id);

    //                     $risk = new ProjectRisk([
    //                         'risk' => $riskData['risk'],
    //                         'objective_id' => $objective->objective_id,
    //                     ]);
    //                     $risk->save();

    //                     Log::info('Risk created with ID: ' . $risk->risk_id);
    //                 } else {
    //                     Log::warning('Risk data is missing for objective ID: ' . $objective->objective_id);
    //                 }
    //             }
    //         }

    //         // Handle activities
    //         if (!empty($request->input('activities'))) {
    //             foreach ($request->input('activities') as $activityData) {
    //                 if (isset($activityData['activity'])) {
    //                     Log::info('Adding new activity to objective ID: ' . $objective->objective_id);

    //                     $activity = new ProjectActivity([
    //                         'activity' => $activityData['activity'],
    //                         'verification' => $activityData['verification'],
    //                         'objective_id' => $objective->objective_id,
    //                     ]);
    //                     $activity->save();

    //                     Log::info('Activity created with ID: ' . $activity->activity_id);

    //                     // Handle timeframes for this activity
    //                     if (!empty($activityData['timeframe']['months'])) {
    //                         foreach ($activityData['timeframe']['months'] as $month => $isActive) {
    //                             Log::info('Adding timeframe for activity ID: ' . $activity->activity_id . ' for month: ' . $month, ['is_active' => $isActive]);

    //                             $timeframe = new ProjectTimeframe([
    //                                 'month' => $month,
    //                                 'is_active' => $isActive,
    //                                 'activity_id' => $activity->activity_id,
    //                             ]);
    //                             $timeframe->save();

    //                             Log::info('Timeframe created with ID: ' . $timeframe->timeframe_id);
    //                         }
    //                     }
    //                 } else {
    //                     Log::warning('Activity data is missing for objective ID: ' . $objective->objective_id);
    //                 }
    //             }
    //         }
    //     });

    //     // return redirect()->route('projects.objectives.index')->with('success', 'Project objective updated successfully!');
    // }

    // 6. Remove the specified objective from storage
    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            Log::info('Starting transaction to delete objective with ID: ' . $id);

            $objective = ProjectObjective::findOrFail($id);
            $objective->results()->delete();
            $objective->risks()->delete();
            $objective->activities()->delete();
            $objective->delete();

            Log::info('Successfully deleted objective with ID: ' . $id);
        });

        // return redirect()->route('projects.objectives.index')->with('success', 'Project objective deleted successfully!');
    }
}
