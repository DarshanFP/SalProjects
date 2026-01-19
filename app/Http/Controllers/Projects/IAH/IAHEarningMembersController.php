<?php

namespace App\Http\Controllers\Projects\IAH;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\IAH\ProjectIAHEarningMembers;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Projects\IAH\StoreIAHEarningMembersRequest;
use App\Http\Requests\Projects\IAH\UpdateIAHEarningMembersRequest;

class IAHEarningMembersController extends Controller
{
    /**
     * Store earning members (multi-row). Overwrites existing data.
     */
    public function store(FormRequest $request, $projectId)
    {
        // Use all() to get all form data including earning_members[] arrays
        // These fields are not in StoreProjectRequest/UpdateProjectRequest validation rules
        $validated = $request->all();
        
        Log::info('IAHEarningMembersController@store - Start', [
            'project_id' => $projectId
        ]);

        DB::beginTransaction();
        try {
            // 1️⃣ Delete old data
            ProjectIAHEarningMembers::where('project_id', $projectId)->delete();
            Log::info('IAHEarningMembersController@store - Old earning members deleted', [
                'project_id' => $projectId
            ]);

            // 2️⃣ Insert new data
            $memberNames      = $validated['member_name'] ?? [];
            $workTypes        = $validated['work_type'] ?? [];
            $monthlyIncomes   = $validated['monthly_income'] ?? [];
            $rowCount         = count($memberNames);

            Log::info('IAHEarningMembersController@store - Inserting new rows', [
                'total_rows' => $rowCount
            ]);

            for ($i = 0; $i < $rowCount; $i++) {
                if (!empty($memberNames[$i]) && !empty($workTypes[$i]) && !empty($monthlyIncomes[$i])) {
                    ProjectIAHEarningMembers::create([
                        'project_id'     => $projectId,
                        'member_name'    => $memberNames[$i],
                        'work_type'      => $workTypes[$i],
                        'monthly_income' => $monthlyIncomes[$i],
                    ]);
                }
            }

            DB::commit();
            Log::info('IAHEarningMembersController@store - Success: data stored', [
                'project_id' => $projectId
            ]);

            return response()->json(['message' => 'IAH earning members details saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHEarningMembersController@store - Error storing earning members', [
                'project_id' => $projectId,
                'error'      => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to save IAH earning members details.'], 500);
        }
    }

    /**
     * Update earning members (overwrites old data).
     */
    public function update(FormRequest $request, $projectId)
    {
        // Use all() to get all form data including earning_members[] arrays
        // These fields are not in StoreProjectRequest/UpdateProjectRequest validation rules
        $validated = $request->all();
        
        Log::info('IAHEarningMembersController@update - Start', [
            'project_id' => $projectId
        ]);

        DB::beginTransaction();
        try {
            // 1️⃣ Delete old data
            Log::info('IAHEarningMembersController@update - Deleting old records', ['project_id' => $projectId]);
            ProjectIAHEarningMembers::where('project_id', $projectId)->delete();

            // 2️⃣ Insert new data
            $memberNames    = $validated['member_name'] ?? [];
            $workTypes      = $validated['work_type'] ?? [];
            $monthlyIncomes = $validated['monthly_income'] ?? [];
            $rowCount       = count($memberNames);

            Log::info('IAHEarningMembersController@update - Inserting new rows', [
                'row_count' => $rowCount
            ]);

            for ($i = 0; $i < $rowCount; $i++) {
                if (!empty($memberNames[$i]) && !empty($workTypes[$i]) && !empty($monthlyIncomes[$i])) {
                    ProjectIAHEarningMembers::create([
                        'project_id'     => $projectId,
                        'member_name'    => $memberNames[$i],
                        'work_type'      => $workTypes[$i],
                        'monthly_income' => $monthlyIncomes[$i],
                    ]);
                }
            }

            DB::commit();
            Log::info('IAHEarningMembersController@update - Success: data updated', [
                'project_id' => $projectId
            ]);

            return response()->json(['message' => 'IAH earning members details updated successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHEarningMembersController@update - Error updating earning members', [
                'project_id' => $projectId,
                'error'      => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to update IAH earning members details.'], 500);
        }
    }

    /**
     * Show existing members (read-only).
     */
    public function show($projectId)
    {
        try {
            Log::info('IAHEarningMembersController@show - Fetching data', ['project_id' => $projectId]);

            $earningMembers = ProjectIAHEarningMembers::where('project_id', $projectId)->get();

            // Return the model collection directly, not a JSON response
            return $earningMembers;
        } catch (\Exception $e) {
            Log::error('IAHEarningMembersController@show - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage(),
            ]);
            return collect([]); // Return empty collection instead of JSON error
        }
    }

    /**
     * Return data for editing (usually for a form).
     */
    public function edit($projectId)
    {
        try {
            Log::info('IAHEarningMembersController@edit - Start', ['project_id' => $projectId]);
            $earningMembers = ProjectIAHEarningMembers::where('project_id', $projectId)->get();

            Log::info('IAHEarningMembersController@edit - Data retrieved', [
                'count' => $earningMembers->count(),
                'data'  => $earningMembers->toArray()
            ]);

            return $earningMembers;
        } catch (\Exception $e) {
            Log::error('IAHEarningMembersController@edit - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Delete all earning members for this project.
     */
    public function destroy($projectId)
    {
        Log::info('IAHEarningMembersController@destroy - Start', ['project_id' => $projectId]);

        DB::beginTransaction();
        try {
            ProjectIAHEarningMembers::where('project_id', $projectId)->delete();
            DB::commit();

            Log::info('IAHEarningMembersController@destroy - Successfully deleted', ['project_id' => $projectId]);
            return response()->json(['message' => 'IAH earning members details deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHEarningMembersController@destroy - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to delete IAH earning members details.'], 500);
        }
    }
}
