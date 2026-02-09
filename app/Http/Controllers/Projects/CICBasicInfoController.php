<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\ProjectCICBasicInfo;
use App\Services\FormDataExtractor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CICBasicInfoController extends Controller
{
    // Store basic information for a project
    public function store(Request $request, $projectId)
    {
        $fillable = array_diff(
            (new ProjectCICBasicInfo())->getFillable(),
            ['project_id', 'cic_basic_info_id']
        );
        $data = FormDataExtractor::forFillable($request, $fillable);

        DB::beginTransaction();
        try {
            Log::info('Storing CIC basic info', ['project_id' => $projectId]);

            $basicInfo = ProjectCICBasicInfo::updateOrCreate(
                ['project_id' => $projectId],
                $data
            );

            DB::commit();
            Log::info('CIC basic info saved successfully', ['project_id' => $projectId]);
            return response()->json($basicInfo, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving CIC basic info', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save CIC basic info.'], 500);
        }
    }

    // Show basic info for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching CIC basic info', ['project_id' => $projectId]);

            $basicInfo = ProjectCICBasicInfo::where('project_id', $projectId)->first();

            if (!$basicInfo) {
                Log::warning('No CIC basic info found', ['project_id' => $projectId]);
                return null; // Return null if no data is found
            }

            return $basicInfo; // Return the basic info model
        } catch (\Exception $e) {
            Log::error('Error fetching CIC basic info', ['error' => $e->getMessage()]);
            return null;
        }
    }


    // Edit basic info for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing CIC basic info', ['project_id' => $projectId]);

            $basicInfo = ProjectCICBasicInfo::where('project_id', $projectId)->firstOrFail();

            // Return the data directly
            return $basicInfo;
        } catch (\Exception $e) {
            Log::error('Error editing CIC basic info', ['error' => $e->getMessage()]);
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
            Log::info('Deleting CIC basic info', ['project_id' => $projectId]);

            $basicInfo = ProjectCICBasicInfo::where('project_id', $projectId)->firstOrFail();
            $basicInfo->delete();

            DB::commit();
            Log::info('CIC basic info deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'CIC basic info deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting CIC basic info', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete CIC basic info.'], 500);
        }
    }
}
