<?php

namespace App\Http\Controllers\Projects\IIES;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\IIES\ProjectIIESFamilyWorkingMembers;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Projects\IIES\StoreIIESFamilyWorkingMembersRequest;
use App\Http\Requests\Projects\IIES\UpdateIIESFamilyWorkingMembersRequest;
use Illuminate\Support\Facades\Validator;

class IIESFamilyWorkingMembersController extends Controller
{
    public function store(FormRequest $request, $projectId)
    {
        $formRequest = StoreIIESFamilyWorkingMembersRequest::createFrom($request);
        $normalized = $formRequest->getNormalizedInput();
        $validator = Validator::make($normalized, $formRequest->rules());
        $validator->validate();
        $validated = $validator->validated();

        $memberNames = $validated['iies_member_name'] ?? [];
        $workNatures = $validated['iies_work_nature'] ?? [];
        $monthlyIncomes = $validated['iies_monthly_income'] ?? [];
        if (! is_array($memberNames)) {
            $memberNames = [];
        }
        if (! is_array($workNatures)) {
            $workNatures = [];
        }
        if (! is_array($monthlyIncomes)) {
            $monthlyIncomes = [];
        }

        if (! $this->isIIESFamilyWorkingMembersMeaningfullyFilled($memberNames, $workNatures, $monthlyIncomes)) {
            Log::info('IIES Family Working Members skipped — no meaningful data', [
                'project_id' => $projectId,
            ]);

            return response()->json(['message' => 'IIES family working members saved successfully.'], 200);
        }

        Log::info('Storing IIES family working members', ['project_id' => $projectId]);

        $project = Project::where('project_id', $projectId)->firstOrFail();

        ProjectIIESFamilyWorkingMembers::where('project_id', $projectId)->delete();

        for ($i = 0; $i < count($memberNames); $i++) {
            if (! empty($memberNames[$i]) && ! empty($workNatures[$i]) && array_key_exists($i, $monthlyIncomes)) {
                ProjectIIESFamilyWorkingMembers::create([
                    'project_id' => $projectId,
                    'iies_member_name' => $memberNames[$i],
                    'iies_work_nature' => $workNatures[$i],
                    'iies_monthly_income' => $monthlyIncomes[$i],
                ]);
            }
        }

        return response()->json(['message' => 'IIES family working members saved successfully.'], 200);
    }

    public function show($projectId)
    {
        try {
            Log::info('Fetching IIES Family Working Members for project', ['project_id' => $projectId]);

            $familyMembers = ProjectIIESFamilyWorkingMembers::where('project_id', $projectId)->get();

            if ($familyMembers->isEmpty()) {
                Log::warning('No IIES Family Working Members found', ['project_id' => $projectId]);
            } else {
                Log::info('Fetched IIES Family Working Members', [
                    'project_id' => $projectId,
                    'data_count' => $familyMembers->count(),
                    'data' => $familyMembers,
                ]);
            }

            return $familyMembers;
        } catch (\Exception $e) {
            Log::error('Error fetching IIES Family Working Members', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function edit($projectId)
    {
        try {
            Log::info('Editing IIES family working members', ['project_id' => $projectId]);

            $project = Project::where('project_id', $projectId)
                ->with('iiesFamilyWorkingMembers')
                ->firstOrFail();

            return view('projects.partials.Edit.IIES.family_working_members', compact('project'));
        } catch (\Exception $e) {
            Log::error('Error fetching IIES family working members for edit', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(FormRequest $request, $projectId)
    {
        $formRequest = UpdateIIESFamilyWorkingMembersRequest::createFrom($request);
        $normalized = $formRequest->getNormalizedInput();
        $validator = Validator::make($normalized, $formRequest->rules());
        $validator->validate();
        $validated = $validator->validated();

        $memberNames = $validated['iies_member_name'] ?? [];
        $workNatures = $validated['iies_work_nature'] ?? [];
        $monthlyIncomes = $validated['iies_monthly_income'] ?? [];
        if (! is_array($memberNames)) {
            $memberNames = [];
        }
        if (! is_array($workNatures)) {
            $workNatures = [];
        }
        if (! is_array($monthlyIncomes)) {
            $monthlyIncomes = [];
        }

        if (! $this->isIIESFamilyWorkingMembersMeaningfullyFilled($memberNames, $workNatures, $monthlyIncomes)) {
            Log::info('IIES Family Working Members skipped — no meaningful data', [
                'project_id' => $projectId,
            ]);

            return response()->json(['message' => 'IIES family working members updated successfully.'], 200);
        }

        Log::info('Updating IIES family working members', ['project_id' => $projectId]);

        ProjectIIESFamilyWorkingMembers::where('project_id', $projectId)->delete();

        for ($i = 0; $i < count($memberNames); $i++) {
            if (! empty($memberNames[$i]) && ! empty($workNatures[$i]) && array_key_exists($i, $monthlyIncomes)) {
                ProjectIIESFamilyWorkingMembers::create([
                    'project_id' => $projectId,
                    'iies_member_name' => $memberNames[$i],
                    'iies_work_nature' => $workNatures[$i],
                    'iies_monthly_income' => $monthlyIncomes[$i],
                ]);
            }
        }

        return response()->json(['message' => 'IIES family working members updated successfully.'], 200);
    }

    public function destroy($projectId)
    {
        Log::info('Deleting IIES family working members', ['project_id' => $projectId]);

        ProjectIIESFamilyWorkingMembers::where('project_id', $projectId)->delete();

        Log::info('IIES family working members deleted successfully', ['project_id' => $projectId]);

        return response()->json(['message' => 'IIES family working members deleted successfully.'], 200);
    }

    /**
     * Guard: returns true only when at least one member row would be created
     * (same condition as create loop: non-empty name + work_nature + array_key_exists for monthly_income).
     */
    private function isIIESFamilyWorkingMembersMeaningfullyFilled(array $memberNames, array $workNatures, array $monthlyIncomes): bool
    {
        for ($i = 0; $i < count($memberNames); $i++) {
            if (
                ! empty($memberNames[$i])
                && ! empty($workNatures[$i])
                && array_key_exists($i, $monthlyIncomes)
            ) {
                return true;
            }
        }

        return false;
    }
}
