<?php

namespace App\Http\Controllers\Projects\CCI;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\CCI\ProjectCCIAnnexedTargetGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnnexedTargetGroupController extends Controller
{
    // Store new annexed target group entries
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing CCI Annexed Target Group', ['project_id' => $projectId]);
            Log::info('Request Data:', $request->all());

            // Loop through each annexed target group entry
            foreach ($request->annexed_target_group as $group) {
                // Log each beneficiary entry data before inserting
                Log::info('Beneficiary Entry:', $group);

                // Create a new entry for each annexed target group
                ProjectCCIAnnexedTargetGroup::create([
                    'project_id' => $projectId,
                    'beneficiary_name' => $group['beneficiary_name'] ?? null,
                    'dob' => $group['dob'] ?? null,
                    'date_of_joining' => $group['date_of_joining'] ?? null,
                    'class_of_study' => $group['class_of_study'] ?? null,
                    'family_background_description' => $group['family_background_description'] ?? null,
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
    public function update(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Updating or Creating CCI Annexed Target Group', ['project_id' => $projectId]);
            Log::info('Request Data:', $request->all());

            // Loop through each annexed target group entry
            foreach ($request->annexed_target_group as $group) {
                // Log each beneficiary entry data before inserting/updating
                Log::info('Beneficiary Entry:', $group);

                // Use updateOrCreate to either update or create a new entry for each annexed target group
                ProjectCCIAnnexedTargetGroup::updateOrCreate(
                    ['project_id' => $projectId, 'beneficiary_name' => $group['beneficiary_name'] ?? null], // Identifying condition
                    [
                        'dob' => $group['dob'] ?? null,
                        'date_of_joining' => $group['date_of_joining'] ?? null,
                        'class_of_study' => $group['class_of_study'] ?? null,
                        'family_background_description' => $group['family_background_description'] ?? null,
                    ] // Fields to update or create
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
    public function show($projectId)
    {
        try {
            Log::info('Fetching CCI Annexed Target Group data', ['project_id' => $projectId]);

            $targetGroup = ProjectCCIAnnexedTargetGroup::where('project_id', $projectId)->get();
            return view('projects.partials.CCI.annexed_target_group_show', compact('targetGroup'));
        } catch (\Exception $e) {
            Log::error('Error fetching CCI Annexed Target Group data', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to fetch Annexed Target Group data.');
        }
    }

    // Edit annexed target group
    public function edit($projectId)
    {
        try {
            Log::info('Editing CCI Annexed Target Group', ['project_id' => $projectId]);

            $targetGroup = ProjectCCIAnnexedTargetGroup::where('project_id', $projectId)->get();
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
