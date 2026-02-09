<?php

namespace App\Http\Controllers\Projects\CCI;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\CCI\ProjectCCIPersonalSituation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Projects\CCI\StoreCCIPersonalSituationRequest;
use App\Http\Requests\Projects\CCI\UpdateCCIPersonalSituationRequest;
use Illuminate\Support\Facades\Validator;

class PersonalSituationController extends Controller
{
    public function store(FormRequest $request, $projectId)
    {
        $formRequest = StoreCCIPersonalSituationRequest::createFrom($request);
        $normalized = $formRequest->getNormalizedInput();
        $validator = Validator::make($normalized, $formRequest->rules());
        $validator->validate();
        $validated = $validator->validated();

        Log::info('Storing CCI Personal Situation', ['project_id' => $projectId]);

        $personalSituation = new ProjectCCIPersonalSituation();
        $personalSituation->project_id = $projectId;
        foreach (array_keys($validated) as $key) {
            $personalSituation->{$key} = $validated[$key];
        }
        $personalSituation->save();

        Log::info('CCI Personal Situation saved successfully', ['project_id' => $projectId]);

        return redirect()->route('projects.edit', $projectId)->with('success', 'Personal Situation saved successfully.');
    }

    public function show($projectId)
    {
        try {
            Log::info('Fetching CCI Personal Situation', ['project_id' => $projectId]);

            $personalSituation = ProjectCCIPersonalSituation::where('project_id', $projectId)->first();

            if (! $personalSituation) {
                Log::warning('No Personal Situation data found', ['project_id' => $projectId]);
                return null;
            }

            return $personalSituation;
        } catch (\Exception $e) {
            Log::error('Error fetching CCI Personal Situation', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function edit($projectId)
    {
        try {
            Log::info('Editing CCI Personal Situation', ['project_id' => $projectId]);

            $personalSituation = ProjectCCIPersonalSituation::where('project_id', $projectId)->firstOrFail();

            return $personalSituation;
        } catch (\Exception $e) {
            Log::error('Error editing CCI Personal Situation', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(FormRequest $request, $projectId)
    {
        $formRequest = UpdateCCIPersonalSituationRequest::createFrom($request);
        $normalized = $formRequest->getNormalizedInput();
        $validator = Validator::make($normalized, $formRequest->rules());
        $validator->validate();
        $validated = $validator->validated();

        Log::info('Updating or Creating CCI Personal Situation', ['project_id' => $projectId]);

        ProjectCCIPersonalSituation::updateOrCreate(
            ['project_id' => $projectId],
            $validated
        );

        Log::info('CCI Personal Situation updated or created successfully', ['project_id' => $projectId]);

        return redirect()->route('projects.edit', $projectId)->with('success', 'Personal Situation updated successfully.');
    }

    public function destroy($projectId)
    {
        Log::info('Deleting CCI Personal Situation', ['project_id' => $projectId]);

        ProjectCCIPersonalSituation::where('project_id', $projectId)->delete();

        Log::info('CCI Personal Situation deleted successfully', ['project_id' => $projectId]);

        return redirect()->route('projects.edit', $projectId)->with('success', 'Personal Situation deleted successfully.');
    }
}
