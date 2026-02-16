<?php


namespace App\Http\Controllers\Projects\LDP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\LDP\ProjectLDPTargetGroup;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;

class TargetGroupController extends Controller
{
    // Store or update the target group
    public function store(FormRequest $request, $projectId)
    {
        $fillable = ['L_beneficiary_name', 'L_family_situation', 'L_nature_of_livelihood', 'L_amount_requested'];
        $data = $request->only($fillable);

        $beneficiaryNames = is_array($data['L_beneficiary_name'] ?? null) ? ($data['L_beneficiary_name'] ?? []) : (isset($data['L_beneficiary_name']) && $data['L_beneficiary_name'] !== '' ? [$data['L_beneficiary_name']] : []);
        $familySituations = is_array($data['L_family_situation'] ?? null) ? ($data['L_family_situation'] ?? []) : (isset($data['L_family_situation']) && $data['L_family_situation'] !== '' ? [$data['L_family_situation']] : []);
        $natureOfLivelihoods = is_array($data['L_nature_of_livelihood'] ?? null) ? ($data['L_nature_of_livelihood'] ?? []) : (isset($data['L_nature_of_livelihood']) && $data['L_nature_of_livelihood'] !== '' ? [$data['L_nature_of_livelihood']] : []);
        $amountRequested = is_array($data['L_amount_requested'] ?? null) ? ($data['L_amount_requested'] ?? []) : (isset($data['L_amount_requested']) && $data['L_amount_requested'] !== '' ? [$data['L_amount_requested']] : []);

        if (! $this->isLDPTargetGroupMeaningfullyFilled($beneficiaryNames, $familySituations, $natureOfLivelihoods, $amountRequested)) {
            Log::info('LDPTargetGroupController@store - Section absent or empty; skipping mutation', [
                'project_id' => $projectId,
            ]);
            return redirect()
                ->route('projects.edit', $projectId)
                ->with('success', 'Target Group saved successfully.');
        }

        DB::beginTransaction();
        try {
            Log::info('Storing LDP Target Group', ['project_id' => $projectId]);

            ProjectLDPTargetGroup::where('project_id', $projectId)->delete();

            foreach ($beneficiaryNames as $index => $name) {
                $nameVal = is_array($name ?? null) ? (reset($name) ?? null) : ($name ?? null);
                $familyVal = is_array($familySituations[$index] ?? null) ? (reset($familySituations[$index]) ?? null) : ($familySituations[$index] ?? null);
                $natureVal = is_array($natureOfLivelihoods[$index] ?? null) ? (reset($natureOfLivelihoods[$index]) ?? null) : ($natureOfLivelihoods[$index] ?? null);
                $amountVal = is_array($amountRequested[$index] ?? null) ? (reset($amountRequested[$index]) ?? null) : ($amountRequested[$index] ?? null);

                if (!is_null($nameVal) || !is_null($familyVal) || !is_null($natureVal) || !is_null($amountVal)) {
                    ProjectLDPTargetGroup::create([
                        'project_id' => $projectId,
                        'L_beneficiary_name' => $nameVal,
                        'L_family_situation' => $familyVal,
                        'L_nature_of_livelihood' => $natureVal,
                        'L_amount_requested' => $amountVal,
                    ]);
                }
            }

            DB::commit();
            Log::info('LDP Target Group saved successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Target Group saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving LDP Target Group', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to save Target Group.');
        }
    }

    // Update the target group
    public function update(FormRequest $request, $projectId)
    {
        // Validation and authorization already done by FormRequest
        // Reuse store logic but with FormRequest
        return $this->store($request, $projectId);
    }

    // Show the target group for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching LDP Target Group', ['project_id' => $projectId]);

            // Fetch target groups for the project
            $targetGroups = ProjectLDPTargetGroup::where('project_id', $projectId)->get();

            if ($targetGroups->isEmpty()) {
                Log::info('No Target Groups found for project', ['project_id' => $projectId]);
                return []; // Return an empty array if no data found
            }

            return $targetGroups; // Return the collection
        } catch (\Exception $e) {
            Log::error('Error fetching LDP Target Group', ['error' => $e->getMessage()]);
            return null; // Return null in case of an error
        }
    }


    // Edit the target group for a project
    public function edit($projectId)
{
    try {
        Log::info('Editing LDP Target Group', ['project_id' => $projectId]);

        // Fetch target groups for the project
        $targetGroups = ProjectLDPTargetGroup::where('project_id', $projectId)->get()->toArray();

        // If no target groups found, initialize an empty array
        if (!$targetGroups) {
            $targetGroups = [];
        }

        Log::info('Target groups fetched: ', ['targetGroups' => $targetGroups]);

        // Pass the data to the view
        return $targetGroups;
    } catch (\Exception $e) {
        Log::error('Error editing Target Group for LDP', ['error' => $e->getMessage()]);
        return redirect()->back()->withErrors(['error' => 'Failed to load Target Group data.']);
    }
}


    // Delete the target group for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting LDP Target Group', ['project_id' => $projectId]);

            ProjectLDPTargetGroup::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('LDP Target Group deleted successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Target Group deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting LDP Target Group', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete Target Group.');
        }
    }

    private function isLDPTargetGroupMeaningfullyFilled(
        array $beneficiaryNames,
        array $familySituations,
        array $natureOfLivelihoods,
        array $amountRequested
    ): bool {
        if ($beneficiaryNames === [] && $familySituations === [] && $natureOfLivelihoods === [] && $amountRequested === []) {
            return false;
        }

        $maxLen = max(
            count($beneficiaryNames),
            count($familySituations),
            count($natureOfLivelihoods),
            count($amountRequested)
        );
        for ($i = 0; $i < $maxLen; $i++) {
            $nameVal = is_array($beneficiaryNames[$i] ?? null) ? (reset($beneficiaryNames[$i]) ?? null) : ($beneficiaryNames[$i] ?? null);
            $familyVal = is_array($familySituations[$i] ?? null) ? (reset($familySituations[$i]) ?? null) : ($familySituations[$i] ?? null);
            $natureVal = is_array($natureOfLivelihoods[$i] ?? null) ? (reset($natureOfLivelihoods[$i]) ?? null) : ($natureOfLivelihoods[$i] ?? null);
            $amountVal = is_array($amountRequested[$i] ?? null) ? (reset($amountRequested[$i]) ?? null) : ($amountRequested[$i] ?? null);

            if ($this->meaningfulString($nameVal) || $this->meaningfulString($familyVal)
                || $this->meaningfulString($natureVal) || $this->meaningfulNumeric($amountVal)) {
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
