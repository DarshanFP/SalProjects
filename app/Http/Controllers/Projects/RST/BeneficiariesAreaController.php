<?php

namespace App\Http\Controllers\Projects\RST;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\RST\ProjectDPRSTBeneficiariesArea;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BeneficiariesAreaController extends Controller
{
    // Store or update beneficiaries area
    public function store(Request $request, $projectId)
{
    DB::beginTransaction();
    try {
        Log::info('Storing Beneficiaries Area for DPRST', ['project_id' => $projectId]);

        // First, delete existing beneficiaries area for the project to handle both create and update
        ProjectDPRSTBeneficiariesArea::where('project_id', $projectId)->delete();

        // Loop through the arrays and store the project area information
        foreach ($request->project_area as $index => $projectArea) {
            ProjectDPRSTBeneficiariesArea::create([
                'project_id' => $projectId,
                'project_area' => $projectArea,
                'category_beneficiary' => $request->category_beneficiary[$index] ?? null,
                'direct_beneficiaries' => $request->direct_beneficiaries[$index] ?? null,
                'indirect_beneficiaries' => $request->indirect_beneficiaries[$index] ?? null,
            ]);
        }

        DB::commit();
        Log::info('Beneficiaries Area saved successfully for DPRST', ['project_id' => $projectId]);
        return response()->json(['message' => 'Beneficiaries Area saved successfully.'], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error saving Beneficiaries Area for DPRST', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to save Beneficiaries Area.'], 500);
    }
}
    // Show beneficiaries area for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching Beneficiaries Area for DPRST', ['project_id' => $projectId]);

            // Fetch all entries for the project
            $RSTBeneficiariesArea = ProjectDPRSTBeneficiariesArea::where('project_id', $projectId)->get();

            if ($RSTBeneficiariesArea->isEmpty()) {
                Log::warning('No Beneficiaries Area data found', ['project_id' => $projectId]);
            }

            return $RSTBeneficiariesArea; // Return the collection (empty or not)
        } catch (\Exception $e) {
            Log::error('Error fetching Beneficiaries Area for DPRST', ['error' => $e->getMessage()]);
            return collect(); // Return an empty collection on error
        }
    }
    // Edit beneficiaries area for a project
    public function edit($projectId)
{
    try {
        Log::info('Editing Beneficiaries Area for DPRST', ['project_id' => $projectId]);

        // Fetch all entries for the project
        $beneficiariesAreas = ProjectDPRSTBeneficiariesArea::where('project_id', $projectId)->get();
        return $beneficiariesAreas; // Return data, not a view
    } catch (\Exception $e) {
        Log::error('Error editing Beneficiaries Area for RST/DP', ['error' => $e->getMessage()]);
        return null;
    }
}
public function update(Request $request, $projectId)
{
    DB::beginTransaction();
    try {
        Log::info('Updating Beneficiaries Area for DPRST', ['project_id' => $projectId]);

        // Validate the incoming data
        $validatedData = $request->validate([
            'project_area' => 'required|array',
            'category_beneficiary' => 'array',
            'direct_beneficiaries' => 'array',
            'indirect_beneficiaries' => 'array',
        ]);

        // Check if the project exists
        $existingAreas = ProjectDPRSTBeneficiariesArea::where('project_id', $projectId)->get();
        if ($existingAreas->isEmpty()) {
            Log::warning('No existing Beneficiaries Area data found to update', ['project_id' => $projectId]);
            return response()->json(['error' => 'No existing data found to update.'], 404);
        }

        // Delete existing beneficiaries area entries
        ProjectDPRSTBeneficiariesArea::where('project_id', $projectId)->delete();

        // Create new entries based on the updated data
        foreach ($request->project_area as $index => $projectArea) {
            ProjectDPRSTBeneficiariesArea::create([
                'project_id' => $projectId,
                'project_area' => $projectArea,
                'category_beneficiary' => $request->category_beneficiary[$index] ?? null,
                'direct_beneficiaries' => $request->direct_beneficiaries[$index] ?? null,
                'indirect_beneficiaries' => $request->indirect_beneficiaries[$index] ?? null,
            ]);
        }

        DB::commit();
        Log::info('Beneficiaries Area updated successfully for DPRST', ['project_id' => $projectId]);
        return response()->json(['message' => 'Beneficiaries Area updated successfully.'], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error updating Beneficiaries Area for DPRST', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to update Beneficiaries Area.'], 500);
    }
}

    // Delete beneficiaries area for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting Beneficiaries Area for DPRST', ['project_id' => $projectId]);

            ProjectDPRSTBeneficiariesArea::where('project_id', $projectId)->delete();
            DB::commit();
            Log::info('Beneficiaries Area deleted successfully for DPRST', ['project_id' => $projectId]);
            return response()->json(['message' => 'Beneficiaries Area deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting Beneficiaries Area for DPRST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete Beneficiaries Area.'], 500);
        }
    }
}
