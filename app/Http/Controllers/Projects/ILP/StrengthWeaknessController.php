<?php

namespace App\Http\Controllers\Projects\ILP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\ILP\ProjectILPBusinessStrengthWeakness;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StrengthWeaknessController extends Controller
{
    // Store or update strengths and weaknesses
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing ILP Strengths and Weaknesses', ['project_id' => $projectId]);

            // Delete existing strengths and weaknesses
            ProjectILPBusinessStrengthWeakness::where('project_id', $projectId)->delete();

            ProjectILPBusinessStrengthWeakness::create([
                'project_id' => $projectId,
                'strengths' => json_encode($request->strengths),
                'weaknesses' => json_encode($request->weaknesses),
            ]);

            DB::commit();
            Log::info('ILP Strengths and Weaknesses saved successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Strengths and weaknesses saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving ILP Strengths and Weaknesses', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save strengths and weaknesses.'], 500);
        }
    }

    // Show strengths and weaknesses for a project
    // public function show($projectId)
    // {
    //     try {
    //         Log::info('Fetching ILP Strengths and Weaknesses', ['project_id' => $projectId]);

    //         $strengthWeakness = ProjectILPBusinessStrengthWeakness::where('project_id', $projectId)->first();
    //         return response()->json($strengthWeakness, 200);
    //     } catch (\Exception $e) {
    //         Log::error('Error fetching ILP Strengths and Weaknesses', ['error' => $e->getMessage()]);
    //         return response()->json(['error' => 'Failed to fetch strengths and weaknesses.'], 500);
    //     }
    // }
    public function show($projectId)
    {
        try {
            Log::info('Fetching ILP Strengths and Weaknesses', ['project_id' => $projectId]);

            $strengthWeakness = ProjectILPBusinessStrengthWeakness::where('project_id', $projectId)->first();

            return [
                'strengths' => $strengthWeakness ? json_decode($strengthWeakness->strengths, true) : [],
                'weaknesses' => $strengthWeakness ? json_decode($strengthWeakness->weaknesses, true) : [],
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching ILP Strengths and Weaknesses', ['error' => $e->getMessage()]);
            return [
                'strengths' => [],
                'weaknesses' => [],
            ];
        }
    }

    // Edit strengths and weaknesses for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing ILP Strengths and Weaknesses', ['project_id' => $projectId]);

            // Fetch the record for the given project
            $strengthWeakness = ProjectILPBusinessStrengthWeakness::where('project_id', $projectId)->first();

            // Decode the JSON fields or initialize empty arrays if no data exists
            $strengths = $strengthWeakness ? json_decode($strengthWeakness->strengths, true) : [];
            $weaknesses = $strengthWeakness ? json_decode($strengthWeakness->weaknesses, true) : [];

            // Return raw data to the view or log if no data exists
            if (!$strengthWeakness) {
                Log::warning('No Strengths and Weaknesses found for the given project', ['project_id' => $projectId]);
            } else {
                Log::info('Fetched Strengths and Weaknesses for Edit', [
                    'strengths' => $strengths,
                    'weaknesses' => $weaknesses,
                ]);
            }

            return [
                'strengths' => $strengths,
                'weaknesses' => $weaknesses,
            ];
        } catch (\Exception $e) {
            Log::error('Error editing ILP Strengths and Weaknesses', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);
            return null; // Return null if an error occurs
        }
    }

    public function update(Request $request, $projectId)
    {
        DB::beginTransaction();

        try {
            Log::info('Updating ILP Strengths and Weaknesses', ['project_id' => $projectId]);

            // Validate request
            $validatedData = $request->validate([
                'strengths' => 'nullable|array',
                'weaknesses' => 'nullable|array',
            ]);

            // Fetch the existing record
            $strengthWeakness = ProjectILPBusinessStrengthWeakness::where('project_id', $projectId)->first();

            if ($strengthWeakness) {
                // Update existing record
                $strengthWeakness->update([
                    'strengths' => json_encode($validatedData['strengths']),
                    'weaknesses' => json_encode($validatedData['weaknesses']),
                ]);
            } else {
                // Create new record if none exists
                ProjectILPBusinessStrengthWeakness::create([
                    'project_id' => $projectId,
                    'strengths' => json_encode($validatedData['strengths']),
                    'weaknesses' => json_encode($validatedData['weaknesses']),
                ]);
            }

            DB::commit();
            Log::info('ILP Strengths and Weaknesses updated successfully', ['project_id' => $projectId]);

            return response()->json(['message' => 'Strengths and weaknesses updated successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating ILP Strengths and Weaknesses', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to update strengths and weaknesses.'], 500);
        }
    }


    // Delete strengths and weaknesses for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting ILP Strengths and Weaknesses', ['project_id' => $projectId]);

            ProjectILPBusinessStrengthWeakness::where('project_id', $projectId)->delete();
            DB::commit();
            Log::info('ILP Strengths and Weaknesses deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Strengths and weaknesses deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting ILP Strengths and Weaknesses', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete strengths and weaknesses.'], 500);
        }
    }
}
