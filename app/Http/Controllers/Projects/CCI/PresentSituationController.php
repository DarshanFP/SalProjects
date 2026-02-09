<?php

namespace App\Http\Controllers\Projects\CCI;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\CCI\ProjectCCIPresentSituation;
use App\Services\FormDataExtractor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Projects\CCI\StoreCCIPresentSituationRequest;
use App\Http\Requests\Projects\CCI\UpdateCCIPresentSituationRequest;

class PresentSituationController extends Controller
{
    // Store new present situation entry
    public function store(FormRequest $request, $projectId)
    {
        $fillable = array_diff(
            (new ProjectCCIPresentSituation())->getFillable(),
            ['project_id', 'CCI_present_situation_id']
        );
        $data = FormDataExtractor::forFillable($request, $fillable);

        DB::beginTransaction();
        try {
            Log::info('Storing CCI Present Situation', ['project_id' => $projectId]);

            $presentSituation = ProjectCCIPresentSituation::updateOrCreate(
                ['project_id' => $projectId],
                $data
            );

            DB::commit();
            Log::info('CCI Present Situation saved successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Present Situation saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving CCI Present Situation', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to save Present Situation.');
        }
    }

    // Show present situation for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching CCI Present Situation', ['project_id' => $projectId]);

            // Fetch the present situation data or return an empty array if not found
            $presentSituation = ProjectCCIPresentSituation::where('project_id', $projectId)->first();

            if (!$presentSituation) {
                Log::warning('No Present Situation data found', ['project_id' => $projectId]);
            }

            return $presentSituation; // Return the present situation model
        } catch (\Exception $e) {
            Log::error('Error fetching CCI Present Situation', ['error' => $e->getMessage()]);
            return null;
        }

    }



    // Edit present situation for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing CCI Present Situation', ['project_id' => $projectId]);

            $presentSituation = ProjectCCIPresentSituation::where('project_id', $projectId)->firstOrFail();
            return $presentSituation;
        } catch (\Exception $e) {
            Log::error('Error editing CCI Present Situation', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Update present situation entry
    public function update(FormRequest $request, $projectId)
    {
        return $this->store($request, $projectId);
    }


    // Delete present situation entry
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting CCI Present Situation', ['project_id' => $projectId]);

            // Delete the present situation entry
            ProjectCCIPresentSituation::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('CCI Present Situation deleted successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Present Situation deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting CCI Present Situation', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete Present Situation.');
        }
    }
}
