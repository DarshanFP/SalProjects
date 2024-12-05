<?php

namespace App\Http\Controllers\Projects\IGE;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IGE\ProjectIGEDevelopmentMonitoring;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DevelopmentMonitoringController extends Controller
{
    // Store or update Development Monitoring data for a project
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing IGE Development Monitoring', ['project_id' => $projectId]);

            // Update or create the Development Monitoring entry
            $developmentMonitoring = ProjectIGEDevelopmentMonitoring::updateOrCreate(
                ['project_id' => $projectId],
                [
                    'proposed_activities' => $request->input('proposed_activities'),
                    'monitoring_methods' => $request->input('monitoring_methods'),
                    'evaluation_process' => $request->input('evaluation_process'),
                    'conclusion' => $request->input('conclusion'),
                ]
            );

            DB::commit();
            Log::info('IGE Development Monitoring saved successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Development Monitoring saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IGE Development Monitoring', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to save Development Monitoring.');
        }
    }

    // Show Development Monitoring for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching IGE Development Monitoring', ['project_id' => $projectId]);

            $developmentMonitoring = ProjectIGEDevelopmentMonitoring::where('project_id', $projectId)->first();

            if (!$developmentMonitoring) {
                Log::warning('No Development Monitoring data found', ['project_id' => $projectId]);
                return null; // Return null if no data is found
            }

            return $developmentMonitoring; // Return the development monitoring model
        } catch (\Exception $e) {
            Log::error('Error fetching IGE Development Monitoring', ['error' => $e->getMessage()]);
            return null;
        }
    }


    // Edit Development Monitoring for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing IGE Development Monitoring', ['project_id' => $projectId]);

            $developmentMonitoring = ProjectIGEDevelopmentMonitoring::where('project_id', $projectId)->first();

            if (!$developmentMonitoring) {
                Log::info('No Development Monitoring data found, creating a new instance');
                $developmentMonitoring = new ProjectIGEDevelopmentMonitoring();
            }

            return $developmentMonitoring;
        } catch (\Exception $e) {
            Log::error('Error editing IGE Development Monitoring', ['error' => $e->getMessage()]);
            return new ProjectIGEDevelopmentMonitoring(); // Return an empty instance
        }
    }


    // Update Development Monitoring for a project
    public function update(Request $request, $projectId)
    {
        return $this->store($request, $projectId); // Reuse store method for update
    }

    // Delete Development Monitoring for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting IGE Development Monitoring', ['project_id' => $projectId]);

            // Delete the Development Monitoring entry
            ProjectIGEDevelopmentMonitoring::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('IGE Development Monitoring deleted successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Development Monitoring deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IGE Development Monitoring', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete Development Monitoring.');
        }
    }
}
