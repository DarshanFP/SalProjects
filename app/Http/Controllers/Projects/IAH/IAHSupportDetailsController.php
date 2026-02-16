<?php

namespace App\Http\Controllers\Projects\IAH;

use App\Http\Controllers\Controller;
use App\Services\FormDataExtractor;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\IAH\ProjectIAHSupportDetails;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Projects\IAH\StoreIAHSupportDetailsRequest;
use App\Http\Requests\Projects\IAH\UpdateIAHSupportDetailsRequest;

class IAHSupportDetailsController extends Controller
{
    /**
     * Store (create) a single row of support details for a project.
     */
    public function store(FormRequest $request, $projectId)
    {
        $fillable = array_diff(
            (new ProjectIAHSupportDetails())->getFillable(),
            ['project_id', 'IAH_support_id']
        );
        $data = FormDataExtractor::forFillable($request, $fillable);

        if (! $this->isIAHSupportDetailsMeaningfullyFilled($data)) {
            Log::info('IAHSupportDetailsController@store - Section absent or empty; skipping mutation', [
                'project_id' => $projectId,
            ]);

            $existing = ProjectIAHSupportDetails::where('project_id', $projectId)->first();

            return response()->json($existing, 200);
        }

        Log::info('IAHSupportDetailsController@store - Start', [
            'project_id' => $projectId
        ]);

        DB::beginTransaction();
        try {
            // Typically only one row, so remove old:
            ProjectIAHSupportDetails::where('project_id', $projectId)->delete();

            $supportDetails = new ProjectIAHSupportDetails();
            $supportDetails->project_id = $projectId;
            $supportDetails->fill($data);
            $supportDetails->save();

            DB::commit();
            Log::info('IAHSupportDetailsController@store - Success', [
                'project_id' => $projectId
            ]);
            return response()->json($supportDetails, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHSupportDetailsController@store - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to save IAH support details.'], 500);
        }
    }

    /**
     * Update an existing support details row.
     */
    public function update(FormRequest $request, $projectId)
    {
        return $this->store($request, $projectId);
    }

    /**
     * Show the existing record.
     */
    public function show($projectId)
    {
        try {
            Log::info('IAHSupportDetailsController@show - Start', ['project_id' => $projectId]);

            $supportDetails = ProjectIAHSupportDetails::where('project_id', $projectId)->first();

            // Return the model object directly, not a JSON response
            return $supportDetails;
        } catch (\Exception $e) {
            Log::error('IAHSupportDetailsController@show - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage()
            ]);
            return null; // Return null instead of JSON error
        }
    }

    /**
     * Edit route for a single record typically returns data or a view.
     */
    public function edit($projectId)
    {
        try {
            Log::info('IAHSupportDetailsController@edit - Start', ['project_id' => $projectId]);

            $supportDetails = ProjectIAHSupportDetails::where('project_id', $projectId)->firstOrFail();
            Log::info('IAHSupportDetailsController@edit - Data retrieved', [
                'id' => $supportDetails->id
            ]);

            // Return data or a view
            return $supportDetails;
        } catch (\Exception $e) {
            Log::error('IAHSupportDetailsController@edit - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Delete the existing record.
     */
    public function destroy($projectId)
    {
        Log::info('IAHSupportDetailsController@destroy - Start', ['project_id' => $projectId]);

        DB::beginTransaction();
        try {
            $supportDetails = ProjectIAHSupportDetails::where('project_id', $projectId)->firstOrFail();
            $supportDetails->delete();

            DB::commit();
            Log::info('IAHSupportDetailsController@destroy - Success', [
                'project_id' => $projectId
            ]);
            return response()->json(['message' => 'IAH support details deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHSupportDetailsController@destroy - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to delete IAH support details.'], 500);
        }
    }

    private function isIAHSupportDetailsMeaningfullyFilled(array $data): bool
    {
        foreach ($data as $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    if (trim((string) $v) !== '') {
                        return true;
                    }
                }
            } else {
                if (trim((string) $value) !== '') {
                    return true;
                }
            }
        }

        return false;
    }
}
