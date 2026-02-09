<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\ProjectEduRUTBasicInfo;
use App\Services\FormDataExtractor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProjectEduRUTBasicInfoController extends Controller
{
    // Store basic information for a project
    public function store(FormRequest $request, $projectId)
    {
        $fillable = array_diff(
            (new ProjectEduRUTBasicInfo())->getFillable(),
            ['project_id', 'operational_area_id']
        );
        $data = FormDataExtractor::forFillable($request, $fillable);

        DB::beginTransaction();
        try {
            Log::info('Storing basic info', ['project_id' => $projectId]);

            $basicInfo = ProjectEduRUTBasicInfo::updateOrCreate(
                ['project_id' => $projectId],
                $data
            );

            DB::commit();
            Log::info('Basic info saved successfully', ['project_id' => $projectId]);
            return response()->json($basicInfo, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving basic info', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save basic info.'], 500);
        }
    }

    // Show basic info for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching basic info', ['project_id' => $projectId]);

            // Fetch the basic info data
            $basicInfo = ProjectEduRUTBasicInfo::where('project_id', $projectId)->first();

            if (!$basicInfo) {
                Log::warning('No Basic Info data found', ['project_id' => $projectId]);
                return null; // Return null if no data is found
            }

            return $basicInfo; // Return the basic info model
        } catch (\Exception $e) {
            Log::error('Error fetching basic info', ['error' => $e->getMessage()]);
            return null;
        }
    }


    // Edit basic info for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing basic info', ['project_id' => $projectId]);

            $basicInfo = ProjectEduRUTBasicInfo::where('project_id', $projectId)->firstOrFail();

            // Return the data directly
            return $basicInfo;
        } catch (\Exception $e) {
            Log::error('Error editing basic info', ['error' => $e->getMessage()]);
            return null;
        }
    }


    // Update basic info for a project
    public function update(FormRequest $request, $projectId)
    {
        return $this->store($request, $projectId);
    }

    // Delete basic info for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting basic info', ['project_id' => $projectId]);

            $basicInfo = ProjectEduRUTBasicInfo::where('project_id', $projectId)->firstOrFail();
            $basicInfo->delete();

            DB::commit();
            Log::info('Basic info deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Basic info deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting basic info', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete basic info.'], 500);
        }
    }
}
