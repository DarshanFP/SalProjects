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
        // Use all() to get all form data including beneficiary_name[], caste[], etc. arrays
        // These fields are not in StoreProjectRequest validation rules
        $validated = $request->all();

        // Check if we're already in a transaction (called from ProjectController@update)
        $inTransaction = DB::transactionLevel() > 0;

        if (!$inTransaction) {
            DB::beginTransaction();
        }

        try {
            Log::info('Storing IGE New Beneficiaries Information', [
                'project_id' => $projectId,
                'in_transaction' => $inTransaction,
                'beneficiary_names_count' => count($validated['beneficiary_name'] ?? []),
                'request_keys' => array_keys($validated)
            ]);

            // Delete existing beneficiaries for the project
            ProjectIGENewBeneficiaries::where('project_id', $projectId)->delete();

            // Insert new beneficiaries
            $beneficiaryNames = $validated['beneficiary_name'] ?? [];
            $castes = $validated['caste'] ?? [];
            $addresses = $validated['address'] ?? [];
            $groupYearOfStudies = $validated['group_year_of_study'] ?? [];
            $familyBackgroundNeeds = $validated['family_background_need'] ?? [];

            $savedCount = 0;
            foreach ($beneficiaryNames as $index => $name) {
                if (!empty(trim($name ?? ''))) {
                    ProjectIGENewBeneficiaries::create([
                        'project_id' => $projectId,
                        'beneficiary_name' => $name,
                        'caste' => $castes[$index] ?? null,
                        'address' => $addresses[$index] ?? null,
                        'group_year_of_study' => $groupYearOfStudies[$index] ?? null,
                        'family_background_need' => $familyBackgroundNeeds[$index] ?? null,
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
