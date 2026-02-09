<?php

namespace App\Http\Controllers\Projects\RST;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\RST\ProjectRSTInstitutionInfo;
use App\Services\FormDataExtractor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Projects\RST\StoreRSTInstitutionInfoRequest;
use App\Http\Requests\Projects\RST\UpdateRSTInstitutionInfoRequest;

class InstitutionInfoController extends Controller
{
    // Store or update institution info
    public function store(FormRequest $request, $projectId)
    {
        $fillable = array_diff(
            (new ProjectRSTInstitutionInfo())->getFillable(),
            ['project_id', 'RST_institution_id']
        );
        $data = FormDataExtractor::forFillable($request, $fillable);

        DB::beginTransaction();
        try {
            Log::info('Storing Institution Info for RST', ['project_id' => $projectId]);

            ProjectRSTInstitutionInfo::updateOrCreate(
                ['project_id' => $projectId],
                $data
            );

            DB::commit();
            Log::info('Institution Info saved successfully for RST', ['project_id' => $projectId]);
            return response()->json(['message' => 'Institution Info saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving Institution Info for RST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save Institution Info.'], 500);
        }
    }

    // Show institution info for a project
    public function show($projectId)
{
    try {
        Log::info('Fetching Institution Info for RST', ['project_id' => $projectId]);

        // Fetch the institution info or return null if not found
        $institutionInfo = ProjectRSTInstitutionInfo::where('project_id', $projectId)->first();

        if (!$institutionInfo) {
            Log::warning('No Institution Info found for RST', ['project_id' => $projectId]);
            return null; // Return null if no data is found
        }

        return $institutionInfo; // Return the institution info model
    } catch (\Exception $e) {
        Log::error('Error fetching Institution Info for RST', ['error' => $e->getMessage()]);
        return null;
    }
}


    // Edit institution info for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing Institution Info for RST', ['project_id' => $projectId]);

            $institutionInfo = ProjectRSTInstitutionInfo::where('project_id', $projectId)->firstOrFail();
            return $institutionInfo;
        } catch (\Exception $e) {
            Log::error('Error editing Institution Info for RST', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Update institution info for a project
    public function update(FormRequest $request, $projectId)
    {
        return $this->store($request, $projectId);
    }


    // Delete institution info for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting Institution Info for RST', ['project_id' => $projectId]);

            ProjectRSTInstitutionInfo::where('project_id', $projectId)->delete();
            DB::commit();
            Log::info('Institution Info deleted successfully for RST', ['project_id' => $projectId]);
            return response()->json(['message' => 'Institution Info deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting Institution Info for RST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete Institution Info.'], 500);
        }
    }
}
