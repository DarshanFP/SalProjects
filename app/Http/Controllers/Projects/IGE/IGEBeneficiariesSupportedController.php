<?php

namespace App\Http\Controllers\Projects\IGE;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IGE\ProjectIGEBeneficiariesSupported;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IGEBeneficiariesSupportedController extends Controller
{
    // Store or update beneficiaries for a project
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing IGE beneficiaries supported', ['project_id' => $projectId]);

            // Validate with nullable fields
            $this->validate($request, [
                'class.*' => 'nullable|string|max:255',
                'total_number.*' => 'nullable|integer|min:0',
            ]);

            // First, delete all existing beneficiaries for the project
            ProjectIGEBeneficiariesSupported::where('project_id', $projectId)->delete();

            //  Insert new beneficiaries
            $classes = $request->input('class', []);
            $totalNumbers = $request->input('total_number', []);

            // Store each beneficiary record
            foreach ($classes as $index => $class) {
                if (!is_null($class) && !is_null($totalNumbers[$index])) {
                    ProjectIGEBeneficiariesSupported::create([
                        'project_id' => $projectId,
                        'class' => $class,
                        'total_number' => $totalNumbers[$index],
                    ]);
                }
            }

            DB::commit();
            Log::info('IGE beneficiaries supported saved successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Beneficiaries supported saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IGE beneficiaries supported', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to save IGE beneficiaries supported.');
        }
    }

    // Show beneficiaries supported for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching IGE beneficiaries supported', ['project_id' => $projectId]);

            $beneficiaries = ProjectIGEBeneficiariesSupported::where('project_id', $projectId)->get();

            if ($beneficiaries->isEmpty()) {
                Log::warning('No beneficiaries supported found for project', ['project_id' => $projectId]);
                return null; // Return null if no data found
            }

            return $beneficiaries; // Return the collection of beneficiaries
        } catch (\Exception $e) {
            Log::error('Error fetching IGE beneficiaries supported', ['error' => $e->getMessage()]);
            return null; // Return null on error
        }
    }


    // Edit beneficiaries supported for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing IGE beneficiaries supported', ['project_id' => $projectId]);

            $beneficiariesSupported = ProjectIGEBeneficiariesSupported::where('project_id', $projectId)->get();

            // Ensure we have a collection
            if (!$beneficiariesSupported instanceof \Illuminate\Database\Eloquent\Collection) {
                $beneficiariesSupported = collect();
            }

            return $beneficiariesSupported; // Return data instead of a view
        } catch (\Exception $e) {
            Log::error('Error editing IGE beneficiaries supported', ['error' => $e->getMessage()]);
            return collect(); // Return an empty collection on error
        }
    }

    // Update beneficiaries supported for a project
    public function update(Request $request, $projectId)
    {
        return $this->store($request, $projectId); // Reuse the store logic for update
    }

    // Delete beneficiaries supported for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting IGE beneficiaries supported', ['project_id' => $projectId]);

            ProjectIGEBeneficiariesSupported::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('IGE beneficiaries supported deleted successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Beneficiaries supported deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IGE beneficiaries supported', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete IGE beneficiaries supported.');
        }
    }
}
