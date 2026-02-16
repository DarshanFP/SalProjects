<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\OldProjects\ProjectEduRUTTargetGroup;
use App\Imports\EduRUTTargetGroupImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;

class EduRUTTargetGroupController extends Controller
{
    public function uploadExcel(Request $request)
    {
        // Excel upload logic is currently disabled
        return response()->json(['message' => 'Excel upload feature is disabled.'], 200);
    }

    // Store target group information for a project
    public function store(FormRequest $request, $projectId)
    {
        $fillable = ['target_group'];
        $data = $request->only($fillable);

        $groups = is_array($data['target_group'] ?? null)
            ? ($data['target_group'] ?? [])
            : (isset($data['target_group']) && $data['target_group'] !== '' ? [$data['target_group']] : []);

        DB::beginTransaction();
        try {
            Log::info('Storing target group data', ['project_id' => $projectId]);

            foreach ($groups as $group) {
                if (!is_array($group)) {
                    continue;
                }
                $beneficiaryName = is_array($group['beneficiary_name'] ?? null) ? (reset($group['beneficiary_name']) ?? null) : ($group['beneficiary_name'] ?? null);
                $caste = is_array($group['caste'] ?? null) ? (reset($group['caste']) ?? null) : ($group['caste'] ?? null);
                $institutionName = is_array($group['institution_name'] ?? null) ? (reset($group['institution_name']) ?? null) : ($group['institution_name'] ?? null);
                $classStandard = is_array($group['class_standard'] ?? null) ? (reset($group['class_standard']) ?? null) : ($group['class_standard'] ?? null);
                $totalTuitionFee = is_array($group['total_tuition_fee'] ?? null) ? (reset($group['total_tuition_fee']) ?? null) : ($group['total_tuition_fee'] ?? null);
                $eligibilityScholarship = is_array($group['eligibility_scholarship'] ?? null) ? (reset($group['eligibility_scholarship']) ?? null) : ($group['eligibility_scholarship'] ?? null);
                $expectedAmount = is_array($group['expected_amount'] ?? null) ? (reset($group['expected_amount']) ?? null) : ($group['expected_amount'] ?? null);
                $contributionFromFamily = is_array($group['contribution_from_family'] ?? null) ? (reset($group['contribution_from_family']) ?? null) : ($group['contribution_from_family'] ?? null);

                ProjectEduRUTTargetGroup::create([
                    'project_id' => $projectId,
                    'beneficiary_name' => $beneficiaryName,
                    'caste' => $caste,
                    'institution_name' => $institutionName,
                    'class_standard' => $classStandard,
                    'total_tuition_fee' => $totalTuitionFee,
                    'eligibility_scholarship' => $eligibilityScholarship,
                    'expected_amount' => $expectedAmount,
                    'contribution_from_family' => $contributionFromFamily,
                ]);
            }

            DB::commit();
            Log::info('Target group data stored successfully', ['project_id' => $projectId]);

            return response()->json(['message' => 'Target group data saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing target group data', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to store target group data.'], 500);
        }
    }


    // Show target group data for a project
    public function show($projectId)
{
    try {
        Log::info('Fetching target group data', ['project_id' => $projectId]);

        $RUTtargetGroups = ProjectEduRUTTargetGroup::where('project_id', $projectId)->get();
        return $RUTtargetGroups; // Return the collection directly
    } catch (\Exception $e) {
        Log::error('Error fetching target group data', ['error' => $e->getMessage()]);
        return collect(); // Return an empty collection on error
    }
}


    // Edit target group data for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing target group data', ['project_id' => $projectId]);

            $targetGroups = ProjectEduRUTTargetGroup::where('project_id', $projectId)->get();

            // Return the data directly so it can be passed to the ProjectController
            return $targetGroups;
        } catch (\Exception $e) {
            Log::error('Error editing target group data', ['error' => $e->getMessage()]);
            return null; // Return null in case of an error
        }
    }


    // Update target group data for a project
    public function update(FormRequest $request, $projectId)
    {
        $fillable = ['target_group'];
        $data = $request->only($fillable);

        $groups = is_array($data['target_group'] ?? null)
            ? ($data['target_group'] ?? [])
            : (isset($data['target_group']) && $data['target_group'] !== '' ? [$data['target_group']] : []);

        if (! $this->isEduRUTTargetGroupMeaningfullyFilled($groups)) {
            Log::info('EduRUTTargetGroupController@update - Section absent or empty; skipping mutation', [
                'project_id' => $projectId,
            ]);

            return response()->json([
                'message' => 'EduRUT target group updated successfully.'
            ], 200);
        }

        DB::beginTransaction();
        try {
            Log::info('Updating target group data', ['project_id' => $projectId]);

            ProjectEduRUTTargetGroup::where('project_id', $projectId)->delete();

            foreach ($groups as $group) {
                if (!is_array($group)) {
                    continue;
                }
                $beneficiaryName = is_array($group['beneficiary_name'] ?? null) ? (reset($group['beneficiary_name']) ?? null) : ($group['beneficiary_name'] ?? null);
                $caste = is_array($group['caste'] ?? null) ? (reset($group['caste']) ?? null) : ($group['caste'] ?? null);
                $institutionName = is_array($group['institution_name'] ?? null) ? (reset($group['institution_name']) ?? null) : ($group['institution_name'] ?? null);
                $classStandard = is_array($group['class_standard'] ?? null) ? (reset($group['class_standard']) ?? null) : ($group['class_standard'] ?? null);
                $totalTuitionFee = is_array($group['total_tuition_fee'] ?? null) ? (reset($group['total_tuition_fee']) ?? null) : ($group['total_tuition_fee'] ?? null);
                $eligibilityScholarship = is_array($group['eligibility_scholarship'] ?? null) ? (reset($group['eligibility_scholarship']) ?? null) : ($group['eligibility_scholarship'] ?? null);
                $expectedAmount = is_array($group['expected_amount'] ?? null) ? (reset($group['expected_amount']) ?? null) : ($group['expected_amount'] ?? null);
                $contributionFromFamily = is_array($group['contribution_from_family'] ?? null) ? (reset($group['contribution_from_family']) ?? null) : ($group['contribution_from_family'] ?? null);

                ProjectEduRUTTargetGroup::create([
                    'project_id' => $projectId,
                    'beneficiary_name' => $beneficiaryName,
                    'caste' => $caste,
                    'institution_name' => $institutionName,
                    'class_standard' => $classStandard,
                    'total_tuition_fee' => $totalTuitionFee,
                    'eligibility_scholarship' => $eligibilityScholarship,
                    'expected_amount' => $expectedAmount,
                    'contribution_from_family' => $contributionFromFamily,
                ]);
            }

            DB::commit();
            Log::info('Target group data updated successfully', ['project_id' => $projectId]);

            return response()->json(['message' => 'Target group data updated successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating target group data', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update target group data.'], 500);
        }
    }


    // Delete target group data for a project
    public function destroy($projectId)
    {
        try {
            Log::info('Deleting target group data', ['project_id' => $projectId]);

            ProjectEduRUTTargetGroup::where('project_id', $projectId)->delete();
            return response()->json(['message' => 'Target group data deleted successfully.'], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting target group data', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete target group data.'], 500);
        }
    }

    /**
     * M1 Guard:
     * Returns true if at least one row contains meaningful data.
     */
    private function isEduRUTTargetGroupMeaningfullyFilled($groups): bool
    {
        if (! is_array($groups) || $groups === []) {
            return false;
        }

        foreach ($groups as $row) {
            if (is_array($row) && $this->rowHasMeaningfulValue($row)) {
                return true;
            }
        }

        return false;
    }

    /**
     * True if any field in the row is meaningful.
     */
    private function rowHasMeaningfulValue(array $row): bool
    {
        foreach ($row as $value) {
            if ($this->meaningfulString($value)) {
                return true;
            }

            if ($this->meaningfulNumeric($value)) {
                return true;
            }
        }

        return false;
    }

    private function meaningfulString($value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    private function meaningfulNumeric($value): bool
    {
        return $value !== null && $value !== '' && is_numeric($value);
    }
}
