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
        // Use all() to get all form data including obeneficiary_name[], ocaste[], etc. arrays
        // These fields are not in StoreProjectRequest validation rules
        $validated = $request->all();
        
        DB::beginTransaction();
        try {
            Log::info('Storing IGE Ongoing Beneficiaries Information', ['project_id' => $projectId]);

            // Delete existing ongoing beneficiaries for the project
            ProjectIGEOngoingBeneficiaries::where('project_id', $projectId)->delete();

            // Insert new ongoing beneficiaries
            $obeneficiaryNames = $validated['obeneficiary_name'] ?? [];
            $ocastes = $validated['ocaste'] ?? [];
            $oaddresses = $validated['oaddress'] ?? [];
            $ocurrentGroupYearOfStudies = $validated['ocurrent_group_year_of_study'] ?? [];
            $operformanceDetails = $validated['operformance_details'] ?? [];
            
            foreach ($obeneficiaryNames as $index => $name) {
                if (!is_null($name)) {
                    ProjectIGEOngoingBeneficiaries::create([
                        'project_id' => $projectId,
                        'obeneficiary_name' => $name,
                        'ocaste' => $ocastes[$index] ?? null,
                        'oaddress' => $oaddresses[$index] ?? null,
                        'ocurrent_group_year_of_study' => $ocurrentGroupYearOfStudies[$index] ?? null,
                        'operformance_details' => $operformanceDetails[$index] ?? null,
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
}
