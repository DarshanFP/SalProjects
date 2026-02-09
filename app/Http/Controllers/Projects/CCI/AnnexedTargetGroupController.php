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
    // Store new annexed target group entries
    public function store(FormRequest $request, $projectId)
    {
        $fillable = ['annexed_target_group'];
        $data = $request->only($fillable);

        $groups = is_array($data['annexed_target_group'] ?? null)
            ? ($data['annexed_target_group'] ?? [])
            : (isset($data['annexed_target_group']) && $data['annexed_target_group'] !== '' ? [$data['annexed_target_group']] : []);

        DB::beginTransaction();
        try {
            Log::info('Storing CCI Annexed Target Group', ['project_id' => $projectId]);

            foreach ($groups as $group) {
                if (!is_array($group)) {
                    continue;
                }
                Log::info('Beneficiary Entry:', $group);

                $beneficiaryName = is_array($group['beneficiary_name'] ?? null) ? (reset($group['beneficiary_name']) ?? null) : ($group['beneficiary_name'] ?? null);
                $dob = is_array($group['dob'] ?? null) ? (reset($group['dob']) ?? null) : ($group['dob'] ?? null);
                $dateOfJoining = is_array($group['date_of_joining'] ?? null) ? (reset($group['date_of_joining']) ?? null) : ($group['date_of_joining'] ?? null);
                $classOfStudy = is_array($group['class_of_study'] ?? null) ? (reset($group['class_of_study']) ?? null) : ($group['class_of_study'] ?? null);
                $familyBackground = is_array($group['family_background_description'] ?? null) ? (reset($group['family_background_description']) ?? null) : ($group['family_background_description'] ?? null);

                ProjectCCIAnnexedTargetGroup::create([
                    'project_id' => $projectId,
                    'beneficiary_name' => $beneficiaryName,
                    'dob' => $dob,
                    'date_of_joining' => $dateOfJoining,
                    'class_of_study' => $classOfStudy,
                    'family_background_description' => $familyBackground,
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

    // Update or create annexed target group entries
    public function update(FormRequest $request, $projectId)
    {
        $fillable = ['annexed_target_group'];
        $data = $request->only($fillable);

        $groups = is_array($data['annexed_target_group'] ?? null)
            ? ($data['annexed_target_group'] ?? [])
            : (isset($data['annexed_target_group']) && $data['annexed_target_group'] !== '' ? [$data['annexed_target_group']] : []);

        DB::beginTransaction();
        try {
            Log::info('Updating or Creating CCI Annexed Target Group', ['project_id' => $projectId]);

            foreach ($groups as $group) {
                if (!is_array($group)) {
                    continue;
                }
                Log::info('Beneficiary Entry:', $group);

                $beneficiaryName = is_array($group['beneficiary_name'] ?? null) ? (reset($group['beneficiary_name']) ?? null) : ($group['beneficiary_name'] ?? null);
                $dob = is_array($group['dob'] ?? null) ? (reset($group['dob']) ?? null) : ($group['dob'] ?? null);
                $dateOfJoining = is_array($group['date_of_joining'] ?? null) ? (reset($group['date_of_joining']) ?? null) : ($group['date_of_joining'] ?? null);
                $classOfStudy = is_array($group['class_of_study'] ?? null) ? (reset($group['class_of_study']) ?? null) : ($group['class_of_study'] ?? null);
                $familyBackground = is_array($group['family_background_description'] ?? null) ? (reset($group['family_background_description']) ?? null) : ($group['family_background_description'] ?? null);

                ProjectCCIAnnexedTargetGroup::updateOrCreate(
                    ['project_id' => $projectId, 'beneficiary_name' => $beneficiaryName],
                    [
                        'dob' => $dob,
                        'date_of_joining' => $dateOfJoining,
                        'class_of_study' => $classOfStudy,
                        'family_background_description' => $familyBackground,
                    ]
                );
            }

            DB::commit();
            Log::info('CCI Annexed Target Group updated or created successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Annexed Target Group updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating or creating CCI Annexed Target Group', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to update Annexed Target Group.');
        }
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
