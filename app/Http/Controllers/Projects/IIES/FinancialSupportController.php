<?php

namespace App\Http\Controllers\Projects\IIES;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IIES\ProjectIIESScopeFinancialSupport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinancialSupportController extends Controller
{
    // Store or update financial support
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing IIES Financial Support', ['project_id' => $projectId]);

            // Create or update the financial support
            ProjectIIESScopeFinancialSupport::updateOrCreate(
                ['project_id' => $projectId],
                [
                    'govt_eligible_scholarship' => $request->govt_eligible_scholarship,
                    'scholarship_amt' => $request->scholarship_amt,
                    'other_eligible_scholarship' => $request->other_eligible_scholarship,
                    'other_scholarship_amt' => $request->other_scholarship_amt,
                    'family_contrib' => $request->family_contrib,
                    'no_contrib_reason' => $request->no_contrib_reason,
                ]
            );

            DB::commit();
            Log::info('IIES Financial Support saved successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Financial Support saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IIES Financial Support', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save Financial Support.'], 500);
        }
    }

    // Show financial support for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching IIES Financial Support', ['project_id' => $projectId]);

            $financialSupport = ProjectIIESScopeFinancialSupport::where('project_id', $projectId)->first();
            return response()->json($financialSupport, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching IIES Financial Support', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch Financial Support.'], 500);
        }
    }

    // Edit financial support for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing IIES Financial Support', ['project_id' => $projectId]);

            $financialSupport = ProjectIIESScopeFinancialSupport::where('project_id', $projectId)->first();
            return view('projects.partials.Edit.IIES.financial_support', compact('financialSupport'));
        } catch (\Exception $e) {
            Log::error('Error editing IIES Financial Support', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Delete financial support for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting IIES Financial Support', ['project_id' => $projectId]);

            ProjectIIESScopeFinancialSupport::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('IIES Financial Support deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Financial Support deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IIES Financial Support', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete Financial Support.'], 500);
        }
    }
}
