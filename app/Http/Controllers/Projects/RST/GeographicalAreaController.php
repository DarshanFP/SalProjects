<?php

namespace App\Http\Controllers\Projects\RST;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\RST\ProjectRSTGeographicalArea;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Projects\RST\StoreRSTGeographicalAreaRequest;
use App\Http\Requests\Projects\RST\UpdateRSTGeographicalAreaRequest;

class GeographicalAreaController extends Controller
{
    // Store or update geographical areas
    public function store(FormRequest $request, $projectId)
    {
    // Use all() instead of validated() because mandal, village, town, no_of_beneficiaries
    // fields are not in StoreProjectRequest validation rules
    // This ensures we get all form data including these fields
    $validated = $request->all();
        
        DB::beginTransaction();
        try {
            Log::info('Storing Geographical Areas for RST', ['project_id' => $projectId]);

            // Delete existing geographical areas for the project
            ProjectRSTGeographicalArea::where('project_id', $projectId)->delete();

            // Insert new geographical area data
            $mandals = $validated['mandal'] ?? [];
            $villages = $validated['village'] ?? [];
            $towns = $validated['town'] ?? [];
            $noOfBeneficiaries = $validated['no_of_beneficiaries'] ?? [];
            
            foreach ($mandals as $index => $mandal) {
                ProjectRSTGeographicalArea::create([
                    'project_id' => $projectId,
                    'mandal' => $mandal,
                    'villages' => $villages[$index] ?? null,
                    'town' => $towns[$index] ?? null,
                    'no_of_beneficiaries' => $noOfBeneficiaries[$index] ?? null,
                ]);
            }

            DB::commit();
            Log::info('Geographical Areas saved successfully for RST', ['project_id' => $projectId]);
            return response()->json(['message' => 'Geographical Areas saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving Geographical Areas for RST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save Geographical Areas.'], 500);
        }
    }

    // Show geographical areas for a project
    public function show($projectId)
{
    try {
        Log::info('Fetching Geographical Areas for RST', ['project_id' => $projectId]);

        $geographicalAreas = ProjectRSTGeographicalArea::where('project_id', $projectId)->get();

        if ($geographicalAreas->isEmpty()) {
            Log::warning('No Geographical Areas found for RST', ['project_id' => $projectId]);
            return collect(); // Return an empty collection if no data is found
        }

        return $geographicalAreas; // Return the geographical area data
    } catch (\Exception $e) {
        Log::error('Error fetching Geographical Areas for RST', ['error' => $e->getMessage()]);
        return null;
    }
}


    // Edit geographical areas for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing Geographical Areas for RST', ['project_id' => $projectId]);

            $geographicalAreas = ProjectRSTGeographicalArea::where('project_id', $projectId)->get();
            return $geographicalAreas;
        } catch (\Exception $e) {
            Log::error('Error editing Geographical Areas for RST', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function update(FormRequest $request, $projectId)
{
    // Use all() instead of validated() because mandal, village, town, no_of_beneficiaries
    // fields are not in UpdateProjectRequest validation rules
    // This ensures we get all form data including these fields
    $validatedData = $request->all();
    
    DB::beginTransaction();
    try {
        Log::info('Updating Geographical Areas for RST', ['project_id' => $projectId]);

        // Fetch existing geographical areas for the project
        $existingAreas = ProjectRSTGeographicalArea::where('project_id', $projectId)->get();

        // Update or insert new geographical area data
        $mandals = $validatedData['mandal'] ?? [];
        $villages = $validatedData['village'] ?? [];
        $towns = $validatedData['town'] ?? [];
        $noOfBeneficiaries = $validatedData['no_of_beneficiaries'] ?? [];
        
        foreach ($mandals as $index => $mandal) {
            $areaData = [
                'project_id' => $projectId,
                'mandal' => $mandal,
                'villages' => $villages[$index] ?? null,
                'town' => $towns[$index] ?? null,
                'no_of_beneficiaries' => $noOfBeneficiaries[$index] ?? null,
            ];

            // Check if a record exists for the mandal
            $existingArea = $existingAreas->where('mandal', $mandal)->first();
            if ($existingArea) {
                // Update the existing record
                $existingArea->update($areaData);
            } else {
                // Create a new record if it doesn't exist
                ProjectRSTGeographicalArea::create($areaData);
            }
        }

        DB::commit();
        Log::info('Geographical Areas updated successfully for RST', ['project_id' => $projectId]);
        return response()->json(['message' => 'Geographical Areas updated successfully.'], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error updating Geographical Areas for RST', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to update Geographical Areas.'], 500);
    }
}


    // Delete geographical areas for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting Geographical Areas for RST', ['project_id' => $projectId]);

            ProjectRSTGeographicalArea::where('project_id', $projectId)->delete();
            DB::commit();
            Log::info('Geographical Areas deleted successfully for RST', ['project_id' => $projectId]);
            return response()->json(['message' => 'Geographical Areas deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting Geographical Areas for RST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete Geographical Areas.'], 500);
        }
    }
}
