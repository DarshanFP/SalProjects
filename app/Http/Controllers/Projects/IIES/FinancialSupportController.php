<?php

namespace App\Http\Controllers\Projects\IIES;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\IIES\ProjectIIESScopeFinancialSupport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Projects\IIES\StoreIIESFinancialSupportRequest;
use App\Http\Requests\Projects\IIES\UpdateIIESFinancialSupportRequest;

class FinancialSupportController extends Controller
{
    // Store or update financial support
    public function store(FormRequest $request, $projectId)
    {
        // Use all() to get all form data including fields not in StoreProjectRequest validation rules
        $validated = $request->all();
        
        DB::beginTransaction();
        try {
            Log::info('Storing IIES Financial Support', ['project_id' => $projectId]);

            // Create or update the financial support
            ProjectIIESScopeFinancialSupport::updateOrCreate(
                ['project_id' => $projectId],
                [
                    'govt_eligible_scholarship' => $validated['govt_eligible_scholarship'] ?? null,
                    'scholarship_amt' => $validated['scholarship_amt'] ?? null,
                    'other_eligible_scholarship' => $validated['other_eligible_scholarship'] ?? null,
                    'other_scholarship_amt' => $validated['other_scholarship_amt'] ?? null,
                    'family_contrib' => $validated['family_contrib'] ?? null,
                    'no_contrib_reason' => $validated['no_contrib_reason'] ?? null,
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
    // public function show($projectId)
    // {
    //     try {
    //         Log::info('Fetching IIES Financial Support', ['project_id' => $projectId]);

    //         $financialSupport = ProjectIIESScopeFinancialSupport::where('project_id', $projectId)->first();
    //         return response()->json($financialSupport, 200);
    //     } catch (\Exception $e) {
    //         Log::error('Error fetching IIES Financial Support', ['error' => $e->getMessage()]);
    //         return response()->json(['error' => 'Failed to fetch Financial Support.'], 500);
    //     }
    // }
//     public function show($projectId)
// {
//     try {
//         Log::info('Fetching IIES Financial Support from DB', ['project_id' => $projectId]);

//         $financialSupport = ProjectIIESScopeFinancialSupport::where('project_id', $projectId)->first();

//         if ($financialSupport) {
//             Log::info('IIES Financial Support retrieved successfully', ['data' => $financialSupport]);
//         } else {
//             Log::warning('IIES Financial Support not found for project', ['project_id' => $projectId]);
//         }

//         return $financialSupport ?? new ProjectIIESScopeFinancialSupport(); // Return an empty model if not found
//     } catch (\Exception $e) {
//         Log::error('Error fetching IIES Financial Support', ['error' => $e->getMessage()]);
//         return new ProjectIIESScopeFinancialSupport(); // Return an empty model on failure
//     }

// }
public function show($project_id)
{
    $IIESFinancialSupport = ProjectIIESScopeFinancialSupport::where('project_id', $project_id)->first();

    if (!$IIESFinancialSupport) {
        Log::warning('IIES Financial Support NOT FOUND, returning empty instance', ['project_id' => $project_id]);
        return new ProjectIIESScopeFinancialSupport();
    }

    Log::info('IIES Financial Support Found', ['data' => $IIESFinancialSupport]);
    return $IIESFinancialSupport;
}



    // Edit financial support for a project
    public function edit($projectId)
{
    try {
        Log::info('🔍 Fetching IIES Financial Support', ['project_id' => $projectId]);

        // Fetch financial support details OR return an empty model
        $IIESFinancialSupport = ProjectIIESScopeFinancialSupport::where('project_id', $projectId)->first();

        if (!$IIESFinancialSupport) {
            Log::warning('⚠️ No IIES Financial Support Found, returning empty object', ['project_id' => $projectId]);
            $IIESFinancialSupport = new ProjectIIESScopeFinancialSupport();
        } else {
            Log::info('✅ ProjectController@edit - IIES Financial Support Found', ['data' => $IIESFinancialSupport]);
        }

        return $IIESFinancialSupport;
    } catch (\Exception $e) {
        Log::error('❌ Error fetching IIES Financial Support', ['error' => $e->getMessage()]);
        return new ProjectIIESScopeFinancialSupport(); // Return empty object on failure
    }
}
public function update(FormRequest $request, $projectId)
{
    // Use all() to get all form data including fields not in UpdateProjectRequest validation rules
    $validated = $request->all();
    
    DB::beginTransaction();

    try {
        Log::info('Updating IIES Financial Support', ['project_id' => $projectId]);

        // Fetch the existing record or create a new one if it doesn't exist
        $IIESFinancialSupport = ProjectIIESScopeFinancialSupport::updateOrCreate(
            ['project_id' => $projectId],
            [
                'govt_eligible_scholarship' => $validated['govt_eligible_scholarship'] ?? null,
                'scholarship_amt' => $validated['scholarship_amt'] ?? null,
                'other_eligible_scholarship' => $validated['other_eligible_scholarship'] ?? null,
                'other_scholarship_amt' => $validated['other_scholarship_amt'] ?? null,
                'family_contrib' => $validated['family_contrib'] ?? null,
                'no_contrib_reason' => $validated['no_contrib_reason'] ?? null,
            ]
        );

        DB::commit();
        Log::info('IIES Financial Support updated successfully', ['project_id' => $projectId]);

        return response()->json(['message' => 'Financial Support updated successfully.'], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error updating IIES Financial Support', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to update Financial Support.'], 500);
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
