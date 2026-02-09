<?php

namespace App\Http\Controllers\Projects\RST;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\RST\ProjectRSTTargetGroupAnnexure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Projects\RST\StoreRSTTargetGroupAnnexureRequest;
use App\Http\Requests\Projects\RST\UpdateRSTTargetGroupAnnexureRequest;

class TargetGroupAnnexureController extends Controller
{
    // Store or update target group annexure
    public function store(FormRequest $request, $projectId)
    {
        $fillable = ['rst_name', 'rst_religion', 'rst_caste', 'rst_education_background', 'rst_family_situation', 'rst_paragraph'];
        $data = $request->only($fillable);

        $rstNames = is_array($data['rst_name'] ?? null) ? ($data['rst_name'] ?? []) : (isset($data['rst_name']) && $data['rst_name'] !== '' ? [$data['rst_name']] : []);
        $rstReligions = is_array($data['rst_religion'] ?? null) ? ($data['rst_religion'] ?? []) : (isset($data['rst_religion']) && $data['rst_religion'] !== '' ? [$data['rst_religion']] : []);
        $rstCastes = is_array($data['rst_caste'] ?? null) ? ($data['rst_caste'] ?? []) : (isset($data['rst_caste']) && $data['rst_caste'] !== '' ? [$data['rst_caste']] : []);
        $rstEducationBackgrounds = is_array($data['rst_education_background'] ?? null) ? ($data['rst_education_background'] ?? []) : (isset($data['rst_education_background']) && $data['rst_education_background'] !== '' ? [$data['rst_education_background']] : []);
        $rstFamilySituations = is_array($data['rst_family_situation'] ?? null) ? ($data['rst_family_situation'] ?? []) : (isset($data['rst_family_situation']) && $data['rst_family_situation'] !== '' ? [$data['rst_family_situation']] : []);
        $rstParagraphs = is_array($data['rst_paragraph'] ?? null) ? ($data['rst_paragraph'] ?? []) : (isset($data['rst_paragraph']) && $data['rst_paragraph'] !== '' ? [$data['rst_paragraph']] : []);

        DB::beginTransaction();
        try {
            Log::info('Storing Target Group Annexure for RST', ['project_id' => $projectId]);

            ProjectRSTTargetGroupAnnexure::where('project_id', $projectId)->delete();

            foreach ($rstNames as $index => $rstName) {
                $rstNameVal = is_array($rstName ?? null) ? (reset($rstName) ?? null) : ($rstName ?? null);
                $rstReligionVal = is_array($rstReligions[$index] ?? null) ? (reset($rstReligions[$index]) ?? null) : ($rstReligions[$index] ?? null);
                $rstCasteVal = is_array($rstCastes[$index] ?? null) ? (reset($rstCastes[$index]) ?? null) : ($rstCastes[$index] ?? null);
                $rstEducationVal = is_array($rstEducationBackgrounds[$index] ?? null) ? (reset($rstEducationBackgrounds[$index]) ?? null) : ($rstEducationBackgrounds[$index] ?? null);
                $rstFamilyVal = is_array($rstFamilySituations[$index] ?? null) ? (reset($rstFamilySituations[$index]) ?? null) : ($rstFamilySituations[$index] ?? null);
                $rstParagraphVal = is_array($rstParagraphs[$index] ?? null) ? (reset($rstParagraphs[$index]) ?? null) : ($rstParagraphs[$index] ?? null);

                ProjectRSTTargetGroupAnnexure::create([
                    'project_id' => $projectId,
                    'rst_name' => $rstNameVal,
                    'rst_religion' => $rstReligionVal,
                    'rst_caste' => $rstCasteVal,
                    'rst_education_background' => $rstEducationVal,
                    'rst_family_situation' => $rstFamilyVal,
                    'rst_paragraph' => $rstParagraphVal,
                ]);
            }

            DB::commit();
            Log::info('Target Group Annexure saved successfully for RST', ['project_id' => $projectId]);
            return response()->json(['message' => 'Target Group Annexure saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving Target Group Annexure for RST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save Target Group Annexure.'], 500);
        }
    }

    // Show target group annexure for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching Target Group Annexure for RST', ['project_id' => $projectId]);

            $RSTTargetGroupAnnexure = ProjectRSTTargetGroupAnnexure::where('project_id', $projectId)->get();

            if ($RSTTargetGroupAnnexure->isEmpty()) {
                Log::warning('No Target Group Annexure data found', ['project_id' => $projectId]);
                return collect(); // Return an empty collection if no data found
            }

            return $RSTTargetGroupAnnexure;
        } catch (\Exception $e) {
            Log::error('Error fetching Target Group Annexure for RST', ['error' => $e->getMessage()]);
            return null; // Return null on error
        }
    }


    // Edit target group annexure for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing Target Group Annexure for RST', ['project_id' => $projectId]);

            // Fetch all entries for the project
            $targetGroupAnnexures = ProjectRSTTargetGroupAnnexure::where('project_id', $projectId)->get();
            return $targetGroupAnnexures;
        } catch (\Exception $e) {
            Log::error('Error editing Target Group Annexure for RST', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Update target group annexure for a project
    public function update(FormRequest $request, $projectId)
    {
        return $this->store($request, $projectId);
    }

    // Delete target group annexure for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting Target Group Annexure for RST', ['project_id' => $projectId]);

            ProjectRSTTargetGroupAnnexure::where('project_id', $projectId)->delete();
            DB::commit();
            Log::info('Target Group Annexure deleted successfully for RST', ['project_id' => $projectId]);
            return response()->json(['message' => 'Target Group Annexure deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting Target Group Annexure for RST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete Target Group Annexure.'], 500);
        }
    }
}
