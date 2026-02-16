<?php

namespace App\Http\Controllers\Projects\RST;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\RST\ProjectDPRSTBeneficiariesArea;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Projects\RST\StoreRSTBeneficiariesAreaRequest;
use App\Http\Requests\Projects\RST\UpdateRSTBeneficiariesAreaRequest;

class BeneficiariesAreaController extends Controller
{
    // Store or update beneficiaries area
    public function store(FormRequest $request, $projectId)
    {
        $fillable = ['project_area', 'category_beneficiary', 'direct_beneficiaries', 'indirect_beneficiaries'];
        $data = $request->only($fillable);

        $projectAreas = is_array($data['project_area'] ?? null) ? ($data['project_area'] ?? []) : (isset($data['project_area']) && $data['project_area'] !== '' ? [$data['project_area']] : []);
        $categoryBeneficiaries = is_array($data['category_beneficiary'] ?? null) ? ($data['category_beneficiary'] ?? []) : (isset($data['category_beneficiary']) && $data['category_beneficiary'] !== '' ? [$data['category_beneficiary']] : []);
        $directBeneficiaries = is_array($data['direct_beneficiaries'] ?? null) ? ($data['direct_beneficiaries'] ?? []) : (isset($data['direct_beneficiaries']) && $data['direct_beneficiaries'] !== '' ? [$data['direct_beneficiaries']] : []);
        $indirectBeneficiaries = is_array($data['indirect_beneficiaries'] ?? null) ? ($data['indirect_beneficiaries'] ?? []) : (isset($data['indirect_beneficiaries']) && $data['indirect_beneficiaries'] !== '' ? [$data['indirect_beneficiaries']] : []);

        // M1 Data Integrity Shield: skip delete+recreate when section is absent or empty.
        if (! $this->isBeneficiariesAreaMeaningfullyFilled($projectAreas, $categoryBeneficiaries, $directBeneficiaries, $indirectBeneficiaries)) {
            Log::info('BeneficiariesAreaController@store - Section absent or empty; skipping mutation', [
                'project_id' => $projectId,
            ]);

            return response()->json(['message' => 'Beneficiaries Area saved successfully.'], 200);
        }

        DB::beginTransaction();
        try {
            Log::info('Storing Beneficiaries Area for DPRST', ['project_id' => $projectId]);

            ProjectDPRSTBeneficiariesArea::where('project_id', $projectId)->delete();

            foreach ($projectAreas as $index => $projectArea) {
                $projectAreaVal = is_array($projectArea ?? null) ? (reset($projectArea) ?? null) : ($projectArea ?? null);
                $categoryVal = is_array($categoryBeneficiaries[$index] ?? null) ? (reset($categoryBeneficiaries[$index]) ?? null) : ($categoryBeneficiaries[$index] ?? null);
                $directVal = is_array($directBeneficiaries[$index] ?? null) ? (reset($directBeneficiaries[$index]) ?? null) : ($directBeneficiaries[$index] ?? null);
                $indirectVal = is_array($indirectBeneficiaries[$index] ?? null) ? (reset($indirectBeneficiaries[$index]) ?? null) : ($indirectBeneficiaries[$index] ?? null);

                ProjectDPRSTBeneficiariesArea::create([
                    'project_id' => $projectId,
                    'project_area' => $projectAreaVal,
                    'category_beneficiary' => $categoryVal,
                    'direct_beneficiaries' => $directVal,
                    'indirect_beneficiaries' => $indirectVal,
                ]);
            }

            DB::commit();
            Log::info('Beneficiaries Area saved successfully for DPRST', ['project_id' => $projectId]);
            return response()->json(['message' => 'Beneficiaries Area saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving Beneficiaries Area for DPRST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save Beneficiaries Area.'], 500);
        }
    }
    // Show beneficiaries area for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching Beneficiaries Area for DPRST', ['project_id' => $projectId]);

            // Fetch all entries for the project
            $RSTBeneficiariesArea = ProjectDPRSTBeneficiariesArea::where('project_id', $projectId)->get();

            if ($RSTBeneficiariesArea->isEmpty()) {
                Log::warning('No Beneficiaries Area data found', ['project_id' => $projectId]);
            }

            return $RSTBeneficiariesArea; // Return the collection (empty or not)
        } catch (\Exception $e) {
            Log::error('Error fetching Beneficiaries Area for DPRST', ['error' => $e->getMessage()]);
            return collect(); // Return an empty collection on error
        }
    }
    // Edit beneficiaries area for a project
    public function edit($projectId)
{
    try {
        Log::info('Editing Beneficiaries Area for DPRST', ['project_id' => $projectId]);

        // Fetch all entries for the project
        $beneficiariesAreas = ProjectDPRSTBeneficiariesArea::where('project_id', $projectId)->get();
        return $beneficiariesAreas; // Return data, not a view
    } catch (\Exception $e) {
        Log::error('Error editing Beneficiaries Area for RST/DP', ['error' => $e->getMessage()]);
        return null;
    }
}
// public function update(Request $request, $projectId)
// {
//     DB::beginTransaction();
//     try {
//         Log::info('Updating Beneficiaries Area for DPRST', ['project_id' => $projectId]);

//         // Validate the incoming data
//         $validatedData = $request->validate([
//             'project_area' => 'required|array',
//             'category_beneficiary' => 'array',
//             'direct_beneficiaries' => 'array',
//             'indirect_beneficiaries' => 'array',
//         ]);

//         // Check if the project exists
//         $existingAreas = ProjectDPRSTBeneficiariesArea::where('project_id', $projectId)->get();
//         if ($existingAreas->isEmpty()) {
//             Log::warning('No existing Beneficiaries Area data found to update', ['project_id' => $projectId]);
//             return response()->json(['error' => 'No existing data found to update.'], 404);
//         }

//         // Delete existing beneficiaries area entries
//         ProjectDPRSTBeneficiariesArea::where('project_id', $projectId)->delete();

//         // Create new entries based on the updated data
//         foreach ($request->project_area as $index => $projectArea) {
//             ProjectDPRSTBeneficiariesArea::create([
//                 'project_id' => $projectId,
//                 'project_area' => $projectArea,
//                 'category_beneficiary' => $request->category_beneficiary[$index] ?? null,
//                 'direct_beneficiaries' => $request->direct_beneficiaries[$index] ?? null,
//                 'indirect_beneficiaries' => $request->indirect_beneficiaries[$index] ?? null,
//             ]);
//         }

//         DB::commit();
//         Log::info('Beneficiaries Area updated successfully for DPRST', ['project_id' => $projectId]);
//         return response()->json(['message' => 'Beneficiaries Area updated successfully.'], 200);
//     } catch (\Exception $e) {
//         DB::rollBack();
//         Log::error('Error updating Beneficiaries Area for DPRST', ['error' => $e->getMessage()]);
//         return response()->json(['error' => 'Failed to update Beneficiaries Area.'], 500);
//     }
// }

    // Delete beneficiaries area for a project

    public function update(FormRequest $request, $projectId)
    {
        return $this->store($request, $projectId);
    }
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting Beneficiaries Area for DPRST', ['project_id' => $projectId]);

            ProjectDPRSTBeneficiariesArea::where('project_id', $projectId)->delete();
            DB::commit();
            Log::info('Beneficiaries Area deleted successfully for DPRST', ['project_id' => $projectId]);
            return response()->json(['message' => 'Beneficiaries Area deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting Beneficiaries Area for DPRST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete Beneficiaries Area.'], 500);
        }
    }

    /**
     * M1 Guard: true only when project_area (section key) has at least one row with meaningful data.
     * Meaningful row = at least one non-empty trimmed string OR at least one non-null numeric.
     */
    private function isBeneficiariesAreaMeaningfullyFilled(
        array $projectAreas,
        array $categoryBeneficiaries,
        array $directBeneficiaries,
        array $indirectBeneficiaries
    ): bool {
        if ($projectAreas === []) {
            return false;
        }

        foreach ($projectAreas as $index => $projectArea) {
            $projectAreaVal = is_array($projectArea ?? null) ? (reset($projectArea) ?? null) : ($projectArea ?? null);
            $categoryVal = is_array($categoryBeneficiaries[$index] ?? null) ? (reset($categoryBeneficiaries[$index]) ?? null) : ($categoryBeneficiaries[$index] ?? null);
            $directVal = is_array($directBeneficiaries[$index] ?? null) ? (reset($directBeneficiaries[$index]) ?? null) : ($directBeneficiaries[$index] ?? null);
            $indirectVal = is_array($indirectBeneficiaries[$index] ?? null) ? (reset($indirectBeneficiaries[$index]) ?? null) : ($indirectBeneficiaries[$index] ?? null);

            if ($this->rowHasMeaningfulValue($projectAreaVal) || $this->rowHasMeaningfulValue($categoryVal)
                || $this->rowHasMeaningfulValue($directVal) || $this->rowHasMeaningfulValue($indirectVal)) {
                return true;
            }
        }

        return false;
    }

    /** Check if a single cell is meaningful: non-empty string (trim) or non-null numeric. */
    private function rowHasMeaningfulValue(mixed $val): bool
    {
        if ($val === null || $val === '') {
            return false;
        }
        if (is_string($val) && trim($val) !== '') {
            return true;
        }
        if (is_numeric($val)) {
            return true;
        }

        return false;
    }
}
