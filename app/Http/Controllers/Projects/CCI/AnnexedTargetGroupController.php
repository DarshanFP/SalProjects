<?php

namespace App\Http\Controllers\Projects\CCI;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\CCI\ProjectCCIAnnexedTargetGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Projects\CCI\StoreCCIAnnexedTargetGroupRequest;
use App\Http\Requests\Projects\CCI\UpdateCCIAnnexedTargetGroupRequest;

class AnnexedTargetGroupController extends Controller
{
    // Store new annexed target group entries (delete-all-then-recreate)
    public function store(FormRequest $request, $projectId)
    {
        $validatedRows = $this->extractValidatedRows($request);

        DB::beginTransaction();
        try {
            Log::info('Storing CCI Annexed Target Group', ['project_id' => $projectId]);

            ProjectCCIAnnexedTargetGroup::where('project_id', $projectId)->delete();

            foreach ($validatedRows as $row) {
                if ($this->isRowFullyEmpty($row)) {
                    continue;
                }
                Log::info('Beneficiary Entry:', $row);
                ProjectCCIAnnexedTargetGroup::create([
                    'project_id' => $projectId,
                    'beneficiary_name' => $row['beneficiary_name'] ?? null,
                    'dob' => $row['dob'] ?? null,
                    'date_of_joining' => $row['date_of_joining'] ?? null,
                    'class_of_study' => $row['class_of_study'] ?? null,
                    'family_background_description' => $row['family_background_description'] ?? null,
                ]);
            }

            DB::commit();
            Log::info('CCI Annexed Target Group saved successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Annexed Target Group created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating CCI Annexed Target Group', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to create Annexed Target Group.');
        }
    }

    // Update annexed target group entries (delete-all-then-recreate)
    public function update(FormRequest $request, $projectId)
    {
        $validatedRows = $this->extractValidatedRows($request);

        DB::beginTransaction();
        try {
            Log::info('Updating CCI Annexed Target Group', ['project_id' => $projectId]);

            ProjectCCIAnnexedTargetGroup::where('project_id', $projectId)->delete();

            foreach ($validatedRows as $row) {
                if ($this->isRowFullyEmpty($row)) {
                    continue;
                }
                Log::info('Beneficiary Entry:', $row);
                ProjectCCIAnnexedTargetGroup::create([
                    'project_id' => $projectId,
                    'beneficiary_name' => $row['beneficiary_name'] ?? null,
                    'dob' => $row['dob'] ?? null,
                    'date_of_joining' => $row['date_of_joining'] ?? null,
                    'class_of_study' => $row['class_of_study'] ?? null,
                    'family_background_description' => $row['family_background_description'] ?? null,
                ]);
            }

            DB::commit();
            Log::info('CCI Annexed Target Group updated successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Annexed Target Group updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating CCI Annexed Target Group', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to update Annexed Target Group.');
        }
    }

    /**
     * Extract and normalize annexed_target_group rows from request (scoped input, scalar coercion).
     */
    private function extractValidatedRows(FormRequest $request): array
    {
        $fillable = ['annexed_target_group'];
        $data = $request->only($fillable);
        $groups = is_array($data['annexed_target_group'] ?? null)
            ? ($data['annexed_target_group'] ?? [])
            : (isset($data['annexed_target_group']) && $data['annexed_target_group'] !== '' ? [$data['annexed_target_group']] : []);

        $rows = [];
        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }
            $rows[] = [
                'beneficiary_name' => is_array($group['beneficiary_name'] ?? null) ? (reset($group['beneficiary_name']) ?? null) : ($group['beneficiary_name'] ?? null),
                'dob' => is_array($group['dob'] ?? null) ? (reset($group['dob']) ?? null) : ($group['dob'] ?? null),
                'date_of_joining' => is_array($group['date_of_joining'] ?? null) ? (reset($group['date_of_joining']) ?? null) : ($group['date_of_joining'] ?? null),
                'class_of_study' => is_array($group['class_of_study'] ?? null) ? (reset($group['class_of_study']) ?? null) : ($group['class_of_study'] ?? null),
                'family_background_description' => is_array($group['family_background_description'] ?? null) ? (reset($group['family_background_description']) ?? null) : ($group['family_background_description'] ?? null),
            ];
        }
        return $rows;
    }

    /**
     * Check if row has no meaningful data (skip fully empty rows).
     */
    private function isRowFullyEmpty(array $row): bool
    {
        return empty($row['beneficiary_name'])
            && empty($row['dob'])
            && empty($row['date_of_joining'])
            && empty($row['class_of_study'])
            && empty($row['family_background_description']);
    }


    // Show existing annexed target group data
    // Updated show method in AnnexedTargetGroupController
public function show($projectId)
{
    try {
        Log::info('Fetching CCI Annexed Target Group data', ['project_id' => $projectId]);

        // Fetch target group data for the project
        $annexedTargetGroup = ProjectCCIAnnexedTargetGroup::where('project_id', $projectId)->get();

        // Log the fetched data
        Log::info('Fetched target group data:', ['data' => $annexedTargetGroup]);

        // Return the fetched data (not a view)
        return $annexedTargetGroup;
    } catch (\Exception $e) {
        Log::error('Error fetching CCI Annexed Target Group data', ['error' => $e->getMessage()]);
        return null;
    }
}





    // Edit annexed target group
    public function edit($projectId)
    {
        try {
            Log::info('Editing CCI Annexed Target Group', ['project_id' => $projectId]);

            $targetGroup = ProjectCCIAnnexedTargetGroup::where('project_id', $projectId)->get() ?? [];
            return $targetGroup;
        } catch (\Exception $e) {
            Log::error('Error editing CCI Annexed Target Group', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Delete annexed target group entries
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting CCI Annexed Target Group', ['project_id' => $projectId]);

            ProjectCCIAnnexedTargetGroup::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('CCI Annexed Target Group deleted successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Annexed Target Group deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting CCI Annexed Target Group', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete Annexed Target Group.');
        }
    }
}
