<?php

namespace App\Http\Controllers\Projects\IIES;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\IIES\ProjectIIESScopeFinancialSupport;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Projects\IIES\StoreIIESFinancialSupportRequest;
use App\Http\Requests\Projects\IIES\UpdateIIESFinancialSupportRequest;
use Illuminate\Support\Facades\Validator;

class FinancialSupportController extends Controller
{
    public function store(FormRequest $request, $projectId)
    {
        $formRequest = StoreIIESFinancialSupportRequest::createFrom($request);
        $normalized = $formRequest->getNormalizedInput();
        $validator = Validator::make($normalized, $formRequest->rules());
        $validator->validate();
        $validated = $validator->validated();

        Log::info('Storing IIES Financial Support', ['project_id' => $projectId]);

        ProjectIIESScopeFinancialSupport::updateOrCreate(
            ['project_id' => $projectId],
            [
                'govt_eligible_scholarship' => $validated['govt_eligible_scholarship'],
                'scholarship_amt' => $validated['scholarship_amt'] ?? 0,
                'other_eligible_scholarship' => $validated['other_eligible_scholarship'],
                'other_scholarship_amt' => $validated['other_scholarship_amt'] ?? 0,
                'family_contrib' => $validated['family_contrib'] ?? 0,
                'no_contrib_reason' => $validated['no_contrib_reason'] ?? null,
            ]
        );

        return response()->json(['message' => 'Financial Support saved successfully.'], 200);
    }

    public function show($project_id)
    {
        $IIESFinancialSupport = ProjectIIESScopeFinancialSupport::where('project_id', $project_id)->first();

        if (! $IIESFinancialSupport) {
            Log::warning('IIES Financial Support NOT FOUND, returning empty instance', ['project_id' => $project_id]);
            return new ProjectIIESScopeFinancialSupport();
        }

        Log::info('IIES Financial Support Found', ['data' => $IIESFinancialSupport]);

        return $IIESFinancialSupport;
    }

    public function edit($projectId)
    {
        try {
            Log::info('Fetching IIES Financial Support', ['project_id' => $projectId]);

            $IIESFinancialSupport = ProjectIIESScopeFinancialSupport::where('project_id', $projectId)->first();

            if (! $IIESFinancialSupport) {
                Log::warning('No IIES Financial Support Found, returning empty object', ['project_id' => $projectId]);
                $IIESFinancialSupport = new ProjectIIESScopeFinancialSupport();
            } else {
                Log::info('ProjectController@edit - IIES Financial Support Found', ['data' => $IIESFinancialSupport]);
            }

            return $IIESFinancialSupport;
        } catch (\Exception $e) {
            Log::error('Error fetching IIES Financial Support', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(FormRequest $request, $projectId)
    {
        $formRequest = UpdateIIESFinancialSupportRequest::createFrom($request);
        $normalized = $formRequest->getNormalizedInput();
        $validator = Validator::make($normalized, $formRequest->rules());
        $validator->validate();
        $validated = $validator->validated();

        Log::info('Updating IIES Financial Support', ['project_id' => $projectId]);

        ProjectIIESScopeFinancialSupport::updateOrCreate(
            ['project_id' => $projectId],
            [
                'govt_eligible_scholarship' => $validated['govt_eligible_scholarship'],
                'scholarship_amt' => $validated['scholarship_amt'] ?? 0,
                'other_eligible_scholarship' => $validated['other_eligible_scholarship'],
                'other_scholarship_amt' => $validated['other_scholarship_amt'] ?? 0,
                'family_contrib' => $validated['family_contrib'] ?? 0,
                'no_contrib_reason' => $validated['no_contrib_reason'] ?? null,
            ]
        );

        return response()->json(['message' => 'Financial Support updated successfully.'], 200);
    }

    public function destroy($projectId)
    {
        Log::info('Deleting IIES Financial Support', ['project_id' => $projectId]);

        ProjectIIESScopeFinancialSupport::where('project_id', $projectId)->delete();

        Log::info('IIES Financial Support deleted successfully', ['project_id' => $projectId]);

        return response()->json(['message' => 'Financial Support deleted successfully.'], 200);
    }
}
