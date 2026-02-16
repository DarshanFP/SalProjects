<?php

namespace App\Http\Controllers\Projects\RST;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\RST\ProjectRSTGeographicalArea;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Projects\RST\StoreRSTGeographicalAreaRequest;
use App\Http\Requests\Projects\RST\UpdateRSTGeographicalAreaRequest;

class GeographicalAreaController extends Controller
{
    // Store or update geographical areas
    public function store(FormRequest $request, $projectId)
    {
        $fillable = ['mandal', 'village', 'town', 'no_of_beneficiaries'];
        $data = $request->only($fillable);

        $mandals = is_array($data['mandal'] ?? null) ? ($data['mandal'] ?? []) : (isset($data['mandal']) && $data['mandal'] !== '' ? [$data['mandal']] : []);
        $villages = is_array($data['village'] ?? null) ? ($data['village'] ?? []) : (isset($data['village']) && $data['village'] !== '' ? [$data['village']] : []);
        $towns = is_array($data['town'] ?? null) ? ($data['town'] ?? []) : (isset($data['town']) && $data['town'] !== '' ? [$data['town']] : []);
        $noOfBeneficiaries = is_array($data['no_of_beneficiaries'] ?? null) ? ($data['no_of_beneficiaries'] ?? []) : (isset($data['no_of_beneficiaries']) && $data['no_of_beneficiaries'] !== '' ? [$data['no_of_beneficiaries']] : []);

        if (! $this->isGeographicalAreaMeaningfullyFilled($mandals, $villages, $towns, $noOfBeneficiaries)) {
            Log::info('GeographicalAreaController@store - Section absent or empty; skipping mutation', [
                'project_id' => $projectId,
            ]);
            return response()->json(['message' => 'Geographical Areas saved successfully.'], 200);
        }

        DB::beginTransaction();
        try {
            Log::info('Storing Geographical Areas for RST', ['project_id' => $projectId]);

            ProjectRSTGeographicalArea::where('project_id', $projectId)->delete();

            foreach ($mandals as $index => $mandal) {
                $mandalVal = is_array($mandal ?? null) ? (reset($mandal) ?? null) : ($mandal ?? null);
                $villagesVal = is_array($villages[$index] ?? null) ? (reset($villages[$index]) ?? null) : ($villages[$index] ?? null);
                $townVal = is_array($towns[$index] ?? null) ? (reset($towns[$index]) ?? null) : ($towns[$index] ?? null);
                $noOfBeneficiariesVal = is_array($noOfBeneficiaries[$index] ?? null) ? (reset($noOfBeneficiaries[$index]) ?? null) : ($noOfBeneficiaries[$index] ?? null);

                ProjectRSTGeographicalArea::create([
                    'project_id' => $projectId,
                    'mandal' => $mandalVal,
                    'villages' => $villagesVal,
                    'town' => $townVal,
                    'no_of_beneficiaries' => $noOfBeneficiariesVal,
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

        if ($geographicalAreas->isEmpty()) {
            Log::warning('No Geographical Areas found for RST', ['project_id' => $projectId]);
            return collect(); // Return an empty collection if no data is found
        }

        return $geographicalAreas; // Return the geographical area data
    } catch (\Exception $e) {
        Log::error('Error fetching Geographical Areas for RST', ['error' => $e->getMessage()]);
        return null;
    }
}


    // Edit geographical areas for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing Geographical Areas for RST', ['project_id' => $projectId]);

            $geographicalAreas = ProjectRSTGeographicalArea::where('project_id', $projectId)->get();
            return $geographicalAreas;
        } catch (\Exception $e) {
            Log::error('Error editing Geographical Areas for RST', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function update(FormRequest $request, $projectId)
    {
        return $this->store($request, $projectId);
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

    private function isGeographicalAreaMeaningfullyFilled(
        array $mandals,
        array $villages,
        array $towns,
        array $noOfBeneficiaries
    ): bool {
        if ($mandals === []) {
            return false;
        }

        foreach ($mandals as $index => $mandal) {
            $mandalVal = is_array($mandal ?? null) ? (reset($mandal) ?? null) : ($mandal ?? null);
            $villagesVal = is_array($villages[$index] ?? null) ? (reset($villages[$index]) ?? null) : ($villages[$index] ?? null);
            $townVal = is_array($towns[$index] ?? null) ? (reset($towns[$index]) ?? null) : ($towns[$index] ?? null);
            $noOfBeneficiariesVal = is_array($noOfBeneficiaries[$index] ?? null) ? (reset($noOfBeneficiaries[$index]) ?? null) : ($noOfBeneficiaries[$index] ?? null);

            if ($this->meaningfulString($mandalVal) || $this->meaningfulNumeric($mandalVal)
                || $this->meaningfulString($villagesVal) || $this->meaningfulNumeric($villagesVal)
                || $this->meaningfulString($townVal) || $this->meaningfulNumeric($townVal)
                || $this->meaningfulString($noOfBeneficiariesVal) || $this->meaningfulNumeric($noOfBeneficiariesVal)) {
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
