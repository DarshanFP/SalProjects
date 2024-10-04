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
        Log::info('Incoming request data - logged in LFW ', $request->all());
        Log::info('Data structure:', $request->all());

        $objectives = $request->input('objectives', []);

        if (empty($objectives)) {
            Log::error('No objectives data provided.');
            return redirect()->back()->withErrors(['error' => 'No objectives data provided.']);
        }

        DB::transaction(function () use ($request, $objectives) {
            Log::info('Starting transaction to store new project objective.');

            $projectId = $request->input('project_id');

            foreach ($objectives as $objectiveIndex => $objectiveData) {
                if (isset($objectiveData['objective'])) {
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
    DB::transaction(function () use ($request, $project_id) {
        Log::info('Starting transaction to update objectives for project ID: ' . $project_id);

        // Delete old objectives, risks, results, and activities
        ProjectObjective::where('project_id', $project_id)->delete();

        foreach ($request->input('objectives', []) as $objectiveData) {
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
