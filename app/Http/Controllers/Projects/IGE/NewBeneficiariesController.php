<?php

namespace App\Http\Controllers\Projects\IGE;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\IGE\ProjectIGENewBeneficiaries;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Projects\IGE\StoreIGENewBeneficiariesRequest;
use App\Http\Requests\Projects\IGE\UpdateIGENewBeneficiariesRequest;

class NewBeneficiariesController extends Controller
{
    // Store or update new beneficiaries for a project
    public function store(FormRequest $request, $projectId, $shouldRedirect = true)
    {
        $fillable = ['beneficiary_name', 'caste', 'address', 'group_year_of_study', 'family_background_need'];
        $data = $request->only($fillable);

        // Scalar-to-array normalization; per-value scalar coercion
        $beneficiaryNames = is_array($data['beneficiary_name'] ?? null) ? ($data['beneficiary_name'] ?? []) : (isset($data['beneficiary_name']) && $data['beneficiary_name'] !== '' ? [$data['beneficiary_name']] : []);
        $castes = is_array($data['caste'] ?? null) ? ($data['caste'] ?? []) : (isset($data['caste']) ? [$data['caste']] : []);
        $addresses = is_array($data['address'] ?? null) ? ($data['address'] ?? []) : (isset($data['address']) ? [$data['address']] : []);
        $groupYearOfStudies = is_array($data['group_year_of_study'] ?? null) ? ($data['group_year_of_study'] ?? []) : (isset($data['group_year_of_study']) ? [$data['group_year_of_study']] : []);
        $familyBackgroundNeeds = is_array($data['family_background_need'] ?? null) ? ($data['family_background_need'] ?? []) : (isset($data['family_background_need']) ? [$data['family_background_need']] : []);

        // Check if we're already in a transaction (called from ProjectController@update)
        $inTransaction = DB::transactionLevel() > 0;

        if (!$inTransaction) {
            DB::beginTransaction();
        }

        try {
            Log::info('Storing IGE New Beneficiaries Information', [
                'project_id' => $projectId,
                'in_transaction' => $inTransaction,
                'beneficiary_names_count' => count($beneficiaryNames),
                'request_keys' => array_keys($data)
            ]);

            // Delete existing beneficiaries for the project
            ProjectIGENewBeneficiaries::where('project_id', $projectId)->delete();

            $savedCount = 0;
            foreach ($beneficiaryNames as $index => $name) {
                $nameVal = is_array($name ?? null) ? (reset($name) ?? '') : ($name ?? '');
                if (!empty(trim($nameVal))) {
                    $caste = is_array($castes[$index] ?? null) ? (reset($castes[$index]) ?? null) : ($castes[$index] ?? null);
                    $address = is_array($addresses[$index] ?? null) ? (reset($addresses[$index]) ?? null) : ($addresses[$index] ?? null);
                    $groupYear = is_array($groupYearOfStudies[$index] ?? null) ? (reset($groupYearOfStudies[$index]) ?? null) : ($groupYearOfStudies[$index] ?? null);
                    $familyBg = is_array($familyBackgroundNeeds[$index] ?? null) ? (reset($familyBackgroundNeeds[$index]) ?? null) : ($familyBackgroundNeeds[$index] ?? null);
                    ProjectIGENewBeneficiaries::create([
                        'project_id' => $projectId,
                        'beneficiary_name' => $nameVal,
                        'caste' => $caste,
                        'address' => $address,
                        'group_year_of_study' => $groupYear,
                        'family_background_need' => $familyBg,
                    ]);
                    $savedCount++;
                }
            }

            if (!$inTransaction) {
                DB::commit();
            }

            Log::info('IGE New Beneficiaries saved successfully', [
                'project_id' => $projectId,
                'saved_count' => $savedCount
            ]);

            if ($shouldRedirect && !$inTransaction) {
                return redirect()->route('projects.edit', $projectId)->with('success', 'New Beneficiaries saved successfully.');
            }

            return true; // Return success when called from update method
        } catch (\Exception $e) {
            if (!$inTransaction) {
                DB::rollBack();
            }
            Log::error('Error saving IGE New Beneficiaries', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($shouldRedirect && !$inTransaction) {
                return redirect()->back()->with('error', 'Failed to save New Beneficiaries.');
            }

            throw $e; // Re-throw when called from update method so parent can handle
        }
    }

    // Show new beneficiaries for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching IGE New Beneficiaries', ['project_id' => $projectId]);

            $newBeneficiaries = ProjectIGENewBeneficiaries::where('project_id', $projectId)->get();

            if ($newBeneficiaries->isEmpty()) {
                Log::warning('No New Beneficiaries data found', ['project_id' => $projectId]);
                return collect(); // Return an empty collection if no data is found
            }

            return $newBeneficiaries; // Return the data as a collection
        } catch (\Exception $e) {
            Log::error('Error fetching IGE New Beneficiaries', ['error' => $e->getMessage()]);
            return collect(); // Return an empty collection on error
        }
    }


    // Edit new beneficiaries for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing IGE New Beneficiaries', ['project_id' => $projectId]);

            $newBeneficiaries = ProjectIGENewBeneficiaries::where('project_id', $projectId)->get();

            if (!$newBeneficiaries instanceof \Illuminate\Database\Eloquent\Collection) {
                $newBeneficiaries = collect();
            }

            return $newBeneficiaries; // Return data instead of a view
        } catch (\Exception $e) {
            Log::error('Error editing IGE New Beneficiaries', ['error' => $e->getMessage()]);
            return collect(); // Return an empty collection on error
        }
    }
    public function update(FormRequest $request, $projectId)
    {
        // Reuse store logic but don't redirect (called from ProjectController@update)
        return $this->store($request, $projectId, false);
    }

    // Delete new beneficiaries for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting IGE New Beneficiaries', ['project_id' => $projectId]);

            ProjectIGENewBeneficiaries::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('IGE New Beneficiaries deleted successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'New Beneficiaries deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IGE New Beneficiaries', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete New Beneficiaries.');
        }
    }
}
