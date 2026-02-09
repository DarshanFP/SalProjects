<?php

namespace App\Http\Controllers\Projects\CCI;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\CCI\ProjectCCIRationale;
use App\Services\FormDataExtractor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Projects\CCI\StoreCCIRationaleRequest;
use App\Http\Requests\Projects\CCI\UpdateCCIRationaleRequest;

class RationaleController extends Controller
{
    // Store new rationale entry
    public function store(FormRequest $request, $projectId)
    {
        $fillable = array_diff(
            (new ProjectCCIRationale())->getFillable(),
            ['project_id', 'CCI_rationale_id']
        );
        $data = FormDataExtractor::forFillable($request, $fillable);

        DB::beginTransaction();
        try {
            Log::info('Storing CCI Rationale', ['project_id' => $projectId]);

            $rationale = ProjectCCIRationale::updateOrCreate(
                ['project_id' => $projectId],
                $data
            );

            DB::commit();
            Log::info('CCI Rationale saved successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Rationale saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving CCI Rationale', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to save Rationale.');
        }
    }

    // Show rationale for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching CCI Rationale', ['project_id' => $projectId]);

            // Fetch rationale or return null if not found
            $rationale = ProjectCCIRationale::where('project_id', $projectId)->first();

            if (!$rationale) {
                Log::warning('No Rationale data found', ['project_id' => $projectId]);
            }

            return $rationale;
        } catch (\Exception $e) {
            Log::error('Error fetching CCI Rationale', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to fetch Rationale.');
        }
    }


    // Edit rationale for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing CCI Rationale', ['project_id' => $projectId]);

            $rationale = ProjectCCIRationale::where('project_id', $projectId)->firstOrFail();
            return $rationale;
        } catch (\Exception $e) {
            Log::error('Error editing CCI Rationale', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Update rationale entry
    public function update(FormRequest $request, $projectId)
    {
        return $this->store($request, $projectId);
    }



    // Delete rationale entry
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting CCI Rationale', ['project_id' => $projectId]);

            // Delete the rationale entry
            ProjectCCIRationale::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('CCI Rationale deleted successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Rationale deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting CCI Rationale', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete Rationale.');
        }
    }
}
