<?php

namespace App\Http\Controllers\Projects\CCI;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\CCI\ProjectCCIRationale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RationaleController extends Controller
{
    // Store new rationale entry
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing CCI Rationale', ['project_id' => $projectId]);

            // Create new rationale entry
            $rationale = new ProjectCCIRationale();
            $rationale->project_id = $projectId;
            $rationale->description = $request->description;
            $rationale->save();

            DB::commit();
            Log::info('CCI Rationale saved successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Rationale saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving CCI Rationale', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to save Rationale.');
        }
    }

    // Show rationale for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching CCI Rationale', ['project_id' => $projectId]);

            $rationale = ProjectCCIRationale::where('project_id', $projectId)->firstOrFail();
            return view('projects.partials.CCI.rationale_show', compact('rationale'));
        } catch (\Exception $e) {
            Log::error('Error fetching CCI Rationale', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to fetch Rationale.');
        }
    }

    // Edit rationale for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing CCI Rationale', ['project_id' => $projectId]);

            $rationale = ProjectCCIRationale::where('project_id', $projectId)->firstOrFail();
            return $rationale;
        } catch (\Exception $e) {
            Log::error('Error editing CCI Rationale', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Update rationale entry
    public function update(Request $request, $projectId)
{
    DB::beginTransaction();
    try {
        Log::info('Updating or Creating CCI Rationale', ['project_id' => $projectId]);
        Log::info('Request data:', $request->all());

        // Use updateOrCreate to either update an existing rationale or create a new one
        $rationale = ProjectCCIRationale::updateOrCreate(
            ['project_id' => $projectId], // Condition to find the record
            ['description' => $request->description] // Data to update or create
        );

        Log::info('Rationale data after update or create:', $rationale->toArray());

        DB::commit();
        Log::info('CCI Rationale updated or created successfully', ['project_id' => $projectId]);
        return redirect()->route('projects.edit', $projectId)->with('success', 'Rationale updated successfully.');
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error updating or creating CCI Rationale', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        return redirect()->back()->with('error', 'Failed to update or create Rationale.');
    }
}



    // Delete rationale entry
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting CCI Rationale', ['project_id' => $projectId]);

            // Delete the rationale entry
            ProjectCCIRationale::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('CCI Rationale deleted successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Rationale deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting CCI Rationale', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete Rationale.');
        }
    }
}
