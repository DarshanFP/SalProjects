<?php

namespace App\Http\Controllers\Projects\IGE;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IGE\ProjectIGENewBeneficiaries;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class NewBeneficiariesController extends Controller
{
    // Store or update new beneficiaries for a project
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing IGE New Beneficiaries Information', ['project_id' => $projectId]);

            // Validate request data (allowing nullable fields)
            $this->validate($request, [
                'beneficiary_name.*' => 'nullable|string|max:255',
                'caste.*' => 'nullable|string|max:255',
                'address.*' => 'nullable|string|max:500',
                'group_year_of_study.*' => 'nullable|string|max:255',
                'family_background_need.*' => 'nullable|string|max:500',
            ]);

            // Delete existing beneficiaries for the project
            ProjectIGENewBeneficiaries::where('project_id', $projectId)->delete();

            // Insert new beneficiaries
            foreach ($request->beneficiary_name as $index => $name) {
                if (!is_null($name)) {
                    ProjectIGENewBeneficiaries::create([
                        'project_id' => $projectId,
                        'beneficiary_name' => $name,
                        'caste' => $request->caste[$index] ?? null,
                        'address' => $request->address[$index] ?? null,
                        'group_year_of_study' => $request->group_year_of_study[$index] ?? null,
                        'family_background_need' => $request->family_background_need[$index] ?? null,
                    ]);
                }
            }

            DB::commit();
            Log::info('IGE New Beneficiaries saved successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'New Beneficiaries saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IGE New Beneficiaries', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to save New Beneficiaries.');
        }
    }

    // Show new beneficiaries for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching IGE New Beneficiaries', ['project_id' => $projectId]);

            $newBeneficiaries = ProjectIGENewBeneficiaries::where('project_id', $projectId)->get();
            return view('projects.partials.IGE.new_beneficiaries_show', compact('newBeneficiaries'));
        } catch (\Exception $e) {
            Log::error('Error fetching IGE New Beneficiaries', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to fetch New Beneficiaries.');
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
    public function update(Request $request, $projectId)
    {
        return $this->store($request, $projectId);
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
