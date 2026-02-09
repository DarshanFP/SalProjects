<?php

namespace App\Http\Controllers\Projects\CCI;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\CCI\ProjectCCIEconomicBackground;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Projects\CCI\StoreCCIEconomicBackgroundRequest;
use App\Http\Requests\Projects\CCI\UpdateCCIEconomicBackgroundRequest;
use Illuminate\Support\Facades\Validator;

class EconomicBackgroundController extends Controller
{
    public function store(FormRequest $request, $projectId)
    {
        $formRequest = StoreCCIEconomicBackgroundRequest::createFrom($request);
        $normalized = $formRequest->getNormalizedInput();
        $validator = Validator::make($normalized, $formRequest->rules());
        $validator->validate();
        $validated = $validator->validated();

        Log::info('Storing CCI Economic Background', ['project_id' => $projectId]);

        $economicBackground = new ProjectCCIEconomicBackground();
        $economicBackground->project_id = $projectId;
        $economicBackground->fill($validated);
        $economicBackground->save();

        Log::info('CCI Economic Background saved successfully', ['project_id' => $projectId]);

        return redirect()->route('projects.edit', $projectId)->with('success', 'Economic Background saved successfully.');
    }

    public function update(FormRequest $request, $projectId)
    {
        $formRequest = UpdateCCIEconomicBackgroundRequest::createFrom($request);
        $normalized = $formRequest->getNormalizedInput();
        $validator = Validator::make($normalized, $formRequest->rules());
        $validator->validate();
        $validated = $validator->validated();

        Log::info('Updating or Creating CCI Economic Background', ['project_id' => $projectId]);

        ProjectCCIEconomicBackground::updateOrCreate(
            ['project_id' => $projectId],
            $validated
        );

        Log::info('CCI Economic Background updated or created successfully', ['project_id' => $projectId]);

        return redirect()->route('projects.edit', $projectId)->with('success', 'Economic Background updated successfully.');
    }

    public function show($projectId)
    {
        try {
            Log::info('Fetching CCI Economic Background', ['project_id' => $projectId]);

            $economicBackground = ProjectCCIEconomicBackground::where('project_id', $projectId)->first();

            if (! $economicBackground) {
                Log::warning('No Economic Background data found', ['project_id' => $projectId]);
                return null;
            }

            return $economicBackground;
        } catch (\Exception $e) {
            Log::error('Error fetching CCI Economic Background', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function edit($projectId)
    {
        try {
            Log::info('Editing CCI Economic Background', ['project_id' => $projectId]);

            $economicBackground = ProjectCCIEconomicBackground::where('project_id', $projectId)->firstOrFail();

            return $economicBackground;
        } catch (\Exception $e) {
            Log::error('Error editing CCI Economic Background', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function destroy($projectId)
    {
        Log::info('Deleting CCI Economic Background', ['project_id' => $projectId]);

        ProjectCCIEconomicBackground::where('project_id', $projectId)->delete();

        Log::info('CCI Economic Background deleted successfully', ['project_id' => $projectId]);

        return redirect()->route('projects.edit', $projectId)->with('success', 'Economic Background deleted successfully.');
    }
}
