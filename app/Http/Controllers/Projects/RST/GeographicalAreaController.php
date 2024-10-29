<?php

namespace App\Http\Controllers\Projects\RST;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\RST\ProjectRSTGeographicalArea;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GeographicalAreaController extends Controller
{
    // Store or update geographical areas
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing Geographical Areas for RST', ['project_id' => $projectId]);

            // Delete existing geographical areas for the project
            ProjectRSTGeographicalArea::where('project_id', $projectId)->delete();

            // Insert new geographical area data
            foreach ($request->mandal as $index => $mandal) {
                ProjectRSTGeographicalArea::create([
                    'project_id' => $projectId,
                    'mandal' => $mandal,
                    'villages' => $request->village[$index],
                    'town' => $request->town[$index],
                    'no_of_beneficiaries' => $request->no_of_beneficiaries[$index],
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
            return response()->json($geographicalAreas, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching Geographical Areas for RST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch Geographical Areas.'], 500);
        }
    }

    // Edit geographical areas for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing Geographical Areas for RST', ['project_id' => $projectId]);

            $geographicalAreas = ProjectRSTGeographicalArea::where('project_id', $projectId)->get();
            return view('projects.partials.Edit.RST.geographical_area', compact('geographicalAreas'));
        } catch (\Exception $e) {
            Log::error('Error editing Geographical Areas for RST', ['error' => $e->getMessage()]);
            return null;
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
