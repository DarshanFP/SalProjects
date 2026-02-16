<?php

namespace App\Http\Controllers\Projects\IGE;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\IGE\ProjectIGEOngoingBeneficiaries;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Projects\IGE\StoreIGEOngoingBeneficiariesRequest;
use App\Http\Requests\Projects\IGE\UpdateIGEOngoingBeneficiariesRequest;

class OngoingBeneficiariesController extends Controller
{
    // Store or update ongoing beneficiaries for a project
    public function store(FormRequest $request, $projectId)
    {
        $fillable = ['obeneficiary_name', 'ocaste', 'oaddress', 'ocurrent_group_year_of_study', 'operformance_details'];
        $data = $request->only($fillable);

        // Scalar-to-array normalization; per-value scalar coercion
        $obeneficiaryNames = is_array($data['obeneficiary_name'] ?? null) ? ($data['obeneficiary_name'] ?? []) : (isset($data['obeneficiary_name']) && $data['obeneficiary_name'] !== '' ? [$data['obeneficiary_name']] : []);
        $ocastes = is_array($data['ocaste'] ?? null) ? ($data['ocaste'] ?? []) : (isset($data['ocaste']) ? [$data['ocaste']] : []);
        $oaddresses = is_array($data['oaddress'] ?? null) ? ($data['oaddress'] ?? []) : (isset($data['oaddress']) ? [$data['oaddress']] : []);
        $ocurrentGroupYearOfStudies = is_array($data['ocurrent_group_year_of_study'] ?? null) ? ($data['ocurrent_group_year_of_study'] ?? []) : (isset($data['ocurrent_group_year_of_study']) ? [$data['ocurrent_group_year_of_study']] : []);
        $operformanceDetails = is_array($data['operformance_details'] ?? null) ? ($data['operformance_details'] ?? []) : (isset($data['operformance_details']) ? [$data['operformance_details']] : []);

        if (! $this->isIGEOngoingBeneficiariesMeaningfullyFilled($obeneficiaryNames)) {
            Log::info('IGEOngoingBeneficiariesController@store - Section absent or empty; skipping mutation', [
                'project_id' => $projectId,
            ]);

            return redirect()->route('projects.edit', $projectId)->with('success', 'Ongoing Beneficiaries saved successfully.');
        }

        DB::beginTransaction();
        try {
            Log::info('Storing IGE Ongoing Beneficiaries Information', ['project_id' => $projectId]);

            // Delete existing ongoing beneficiaries for the project
            ProjectIGEOngoingBeneficiaries::where('project_id', $projectId)->delete();

            foreach ($obeneficiaryNames as $index => $name) {
                $nameVal = is_array($name ?? null) ? (reset($name) ?? null) : ($name ?? null);
                if ($nameVal !== null) {
                    $ocaste = is_array($ocastes[$index] ?? null) ? (reset($ocastes[$index]) ?? null) : ($ocastes[$index] ?? null);
                    $oaddress = is_array($oaddresses[$index] ?? null) ? (reset($oaddresses[$index]) ?? null) : ($oaddresses[$index] ?? null);
                    $ocurrentGroup = is_array($ocurrentGroupYearOfStudies[$index] ?? null) ? (reset($ocurrentGroupYearOfStudies[$index]) ?? null) : ($ocurrentGroupYearOfStudies[$index] ?? null);
                    $operf = is_array($operformanceDetails[$index] ?? null) ? (reset($operformanceDetails[$index]) ?? null) : ($operformanceDetails[$index] ?? null);
                    ProjectIGEOngoingBeneficiaries::create([
                        'project_id' => $projectId,
                        'obeneficiary_name' => $nameVal,
                        'ocaste' => $ocaste,
                        'oaddress' => $oaddress,
                        'ocurrent_group_year_of_study' => $ocurrentGroup,
                        'operformance_details' => $operf,
                    ]);
                }
            }

            DB::commit();
            Log::info('IGE Ongoing Beneficiaries saved successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Ongoing Beneficiaries saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IGE Ongoing Beneficiaries', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to save Ongoing Beneficiaries.');
        }
    }


    // Update ongoing beneficiaries for a project
    public function update(FormRequest $request, $projectId)
    {
        // Reuse the store logic for updating
        return $this->store($request, $projectId);
    }

    // Show ongoing beneficiaries for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching IGE Ongoing Beneficiaries', ['project_id' => $projectId]);

            $ongoingBeneficiaries = ProjectIGEOngoingBeneficiaries::where('project_id', $projectId)->get();

            if (!$ongoingBeneficiaries instanceof \Illuminate\Database\Eloquent\Collection) {
                $ongoingBeneficiaries = collect();
            }

            return $ongoingBeneficiaries;
        } catch (\Exception $e) {
            Log::error('Error fetching IGE Ongoing Beneficiaries', ['error' => $e->getMessage()]);
            return collect(); // Return an empty collection on error
        }
    }


    // Edit ongoing beneficiaries for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing IGE Ongoing Beneficiaries', ['project_id' => $projectId]);

            $ongoingBeneficiaries = ProjectIGEOngoingBeneficiaries::where('project_id', $projectId)->get();

            if (!$ongoingBeneficiaries instanceof \Illuminate\Database\Eloquent\Collection) {
                $ongoingBeneficiaries = collect();
            }

            return $ongoingBeneficiaries; // Return data instead of a view
        } catch (\Exception $e) {
            Log::error('Error editing IGE Ongoing Beneficiaries', ['error' => $e->getMessage()]);
            return collect(); // Return an empty collection on error
        }
    }





    // Delete ongoing beneficiaries for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting IGE Ongoing Beneficiaries', ['project_id' => $projectId]);

            ProjectIGEOngoingBeneficiaries::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('IGE Ongoing Beneficiaries deleted successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Ongoing Beneficiaries deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IGE Ongoing Beneficiaries', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete Ongoing Beneficiaries.');
        }
    }

    private function isIGEOngoingBeneficiariesMeaningfullyFilled(array $names): bool
    {
        if ($names === []) {
            return false;
        }
        foreach ($names as $name) {
            $val = is_array($name ?? null) ? (reset($name) ?? '') : ($name ?? '');
            if ($this->meaningfulString($val)) {
                return true;
            }
        }
        return false;
    }

    private function meaningfulString($value): bool
    {
        return is_string($value) && trim($value) !== '';
    }
}
