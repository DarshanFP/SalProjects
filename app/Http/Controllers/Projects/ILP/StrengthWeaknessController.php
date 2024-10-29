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
    public function show($projectId)
    {
        try {
            Log::info('Fetching ILP Strengths and Weaknesses', ['project_id' => $projectId]);

            $strengthWeakness = ProjectILPBusinessStrengthWeakness::where('project_id', $projectId)->first();
            return response()->json($strengthWeakness, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching ILP Strengths and Weaknesses', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch strengths and weaknesses.'], 500);
        }
    }

    // Edit strengths and weaknesses for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing ILP Strengths and Weaknesses', ['project_id' => $projectId]);

            $strengthWeakness = ProjectILPBusinessStrengthWeakness::where('project_id', $projectId)->first();
            $strengths = json_decode($strengthWeakness->strengths) ?? [];
            $weaknesses = json_decode($strengthWeakness->weaknesses) ?? [];

            return view('projects.partials.Edit.ILP.strength_weakness', compact('strengths', 'weaknesses'));
        } catch (\Exception $e) {
            Log::error('Error editing ILP Strengths and Weaknesses', ['error' => $e->getMessage()]);
            return null;
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
