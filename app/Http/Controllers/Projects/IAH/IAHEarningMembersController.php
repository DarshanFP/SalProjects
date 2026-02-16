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
        $fillable = ['member_name', 'work_type', 'monthly_income'];
        $data = $request->only($fillable);

        // Scalar-to-array normalization (same as create loop)
        $memberNames    = is_array($data['member_name'] ?? null) ? ($data['member_name'] ?? []) : (isset($data['member_name']) && $data['member_name'] !== '' ? [$data['member_name']] : []);
        $workTypes      = is_array($data['work_type'] ?? null) ? ($data['work_type'] ?? []) : (isset($data['work_type']) && $data['work_type'] !== '' ? [$data['work_type']] : []);
        $monthlyIncomes = is_array($data['monthly_income'] ?? null) ? ($data['monthly_income'] ?? []) : (isset($data['monthly_income']) && $data['monthly_income'] !== '' ? [$data['monthly_income']] : []);

        if (! $this->isIAHEarningMembersMeaningfullyFilled($memberNames, $workTypes, $monthlyIncomes)) {
            Log::info('IAHEarningMembersController@store - Section absent or empty; skipping mutation', [
                'project_id' => $projectId,
            ]);

            return response()->json([
                'message' => 'IAH earning members details saved successfully.',
            ], 200);
        }

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

            $rowCount = count($memberNames);

            Log::info('IAHEarningMembersController@store - Inserting new rows', [
                'total_rows' => $rowCount
            ]);

            for ($i = 0; $i < $rowCount; $i++) {
                $memberName   = is_array($memberNames[$i] ?? null) ? (reset($memberNames[$i]) ?? '') : ($memberNames[$i] ?? '');
                $workType     = is_array($workTypes[$i] ?? null) ? (reset($workTypes[$i]) ?? '') : ($workTypes[$i] ?? '');
                $monthlyIncome = is_array($monthlyIncomes[$i] ?? null) ? (reset($monthlyIncomes[$i]) ?? '') : ($monthlyIncomes[$i] ?? '');
                // M2.5: Allow 0 for monthly_income; skip only when null or '' (do not use empty() on numeric)
                if (!empty($memberName) && !empty($workType) && $monthlyIncome !== null && $monthlyIncome !== '') {
                    ProjectIAHEarningMembers::create([
                        'project_id'     => $projectId,
                        'member_name'    => $memberName,
                        'work_type'      => $workType,
                        'monthly_income' => $monthlyIncome,
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
        return $this->store($request, $projectId);
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

    private function isIAHEarningMembersMeaningfullyFilled(
        array $memberNames,
        array $workTypes,
        array $monthlyIncomes
    ): bool {
        $rowCount = count($memberNames);

        for ($i = 0; $i < $rowCount; $i++) {
            $memberName = $memberNames[$i] ?? null;
            $workType = $workTypes[$i] ?? null;
            $monthlyIncome = $monthlyIncomes[$i] ?? null;

            if (
                ! empty($memberName) &&
                ! empty($workType) &&
                $monthlyIncome !== null &&
                $monthlyIncome !== ''
            ) {
                return true;
            }
        }

        return false;
    }
}
