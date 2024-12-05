<?php

namespace App\Http\Controllers\Projects\IGE;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IGE\ProjectIGEBudget;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IGEBudgetController extends Controller
{
    // Store or update budget for a project
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing IGE budget', ['project_id' => $projectId]);

            // Validate the request data (allowing nullable fields)
            $this->validate($request, [
                'name.*' => 'nullable|string|max:255',
                'study_proposed.*' => 'nullable|string|max:255',
                'college_fees.*' => 'nullable|numeric|min:0',
                'hostel_fees.*' => 'nullable|numeric|min:0',
                'total_amount.*' => 'nullable|numeric|min:0',
                'scholarship_eligibility.*' => 'nullable|numeric|min:0',
                'family_contribution.*' => 'nullable|numeric|min:0',
                'amount_requested.*' => 'nullable|numeric|min:0',
            ]);

            // First, delete all existing budget records for the project
            ProjectIGEBudget::where('project_id', $projectId)->delete();

            // Insert new budget entries
            $names = $request->input('name', []);
            $studiesProposed = $request->input('study_proposed', []);
            $collegeFees = $request->input('college_fees', []);
            $hostelFees = $request->input('hostel_fees', []);
            $totalAmounts = $request->input('total_amount', []);
            $scholarshipEligibility = $request->input('scholarship_eligibility', []);
            $familyContributions = $request->input('family_contribution', []);
            $amountRequested = $request->input('amount_requested', []);

            // Store each budget row
            foreach ($names as $i => $name) {
                if (!empty($name)) {
                    ProjectIGEBudget::create([
                        'project_id' => $projectId,
                        'name' => $name,
                        'study_proposed' => $studiesProposed[$i] ?? null,
                        'college_fees' => $collegeFees[$i] ?? null,
                        'hostel_fees' => $hostelFees[$i] ?? null,
                        'total_amount' => $totalAmounts[$i] ?? null,
                        'scholarship_eligibility' => $scholarshipEligibility[$i] ?? null,
                        'family_contribution' => $familyContributions[$i] ?? null,
                        'amount_requested' => $amountRequested[$i] ?? null,
                    ]);
                }
            }

            DB::commit();
            Log::info('IGE budget saved successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'IGE budget saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IGE budget', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to save IGE budget.');
        }
    }

    // Show budget for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching IGE budget', ['project_id' => $projectId]);

            $budget = ProjectIGEBudget::where('project_id', $projectId)->get();

            if ($budget->isEmpty()) {
                Log::warning('No IGE budget data found', ['project_id' => $projectId]);
                return collect(); // Return an empty collection if no data
            }

            return $budget; // Return the collection of budget records
        } catch (\Exception $e) {
            Log::error('Error fetching IGE budget', ['error' => $e->getMessage()]);
            return collect(); // Return an empty collection on error
        }
    }


    // Edit budget for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing IGE budget', ['project_id' => $projectId]);

            $budget = ProjectIGEBudget::where('project_id', $projectId)->get();

            if (!$budget instanceof \Illuminate\Database\Eloquent\Collection) {
                $budget = collect();
            }

            return $budget; // Return data instead of a view
        } catch (\Exception $e) {
            Log::error('Error editing IGE budget', ['error' => $e->getMessage()]);
            return collect(); // Return an empty collection on error
        }
    }

    // Update budget for a project
    public function update(Request $request, $projectId)
    {
        return $this->store($request, $projectId); // Reuse the store logic for update
    }

    // Delete budget for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting IGE budget', ['project_id' => $projectId]);

            ProjectIGEBudget::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('IGE budget deleted successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'IGE budget deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IGE budget', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete IGE budget.');
        }
    }
}
