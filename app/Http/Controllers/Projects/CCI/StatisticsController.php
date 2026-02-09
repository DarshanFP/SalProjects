<?php

namespace App\Http\Controllers\Projects\CCI;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\CCI\ProjectCCIStatistics;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Projects\CCI\StoreCCIStatisticsRequest;
use App\Http\Requests\Projects\CCI\UpdateCCIStatisticsRequest;
use Illuminate\Support\Facades\Validator;

class StatisticsController extends Controller
{
    public function store(FormRequest $request, $projectId)
    {
        $formRequest = StoreCCIStatisticsRequest::createFrom($request);
        $normalized = $formRequest->getNormalizedInput();
        $validator = Validator::make($normalized, $formRequest->rules());
        $validator->validate();
        $validated = $validator->validated();

        Log::info('Storing CCI Statistics', ['project_id' => $projectId]);

        $statistics = new ProjectCCIStatistics();
        $statistics->project_id = $projectId;
        foreach (array_keys($validated) as $key) {
            $statistics->{$key} = $validated[$key];
        }
        $statistics->save();

        Log::info('CCI Statistics saved successfully', ['project_id' => $projectId]);

        return redirect()->route('projects.edit', $projectId)->with('success', 'Statistics saved successfully.');
    }

    public function show($projectId)
    {
        try {
            Log::info('Fetching CCI Statistics', ['project_id' => $projectId]);

            $statistics = ProjectCCIStatistics::where('project_id', $projectId)->first();

            if (! $statistics) {
                Log::warning('No Statistics data found', ['project_id' => $projectId]);
            }

            return $statistics;
        } catch (\Exception $e) {
            Log::error('Error fetching CCI Statistics', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function edit($projectId)
    {
        try {
            Log::info('Editing CCI Statistics', ['project_id' => $projectId]);

            $statistics = ProjectCCIStatistics::where('project_id', $projectId)->firstOrFail();

            return $statistics;
        } catch (\Exception $e) {
            Log::error('Error editing CCI Statistics', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(FormRequest $request, $projectId)
    {
        $formRequest = UpdateCCIStatisticsRequest::createFrom($request);
        $normalized = $formRequest->getNormalizedInput();
        $validator = Validator::make($normalized, $formRequest->rules());
        $validator->validate();
        $validated = $validator->validated();

        Log::info('Updating CCI Statistics', ['project_id' => $projectId]);

        $statistics = ProjectCCIStatistics::updateOrCreate(
            ['project_id' => $projectId],
            $validated
        );

        Log::info('CCI Statistics updated successfully', ['project_id' => $projectId]);

        return redirect()->route('projects.edit', $projectId)->with('success', 'Statistics updated successfully.');
    }

    public function destroy($projectId)
    {
        Log::info('Deleting CCI Statistics', ['project_id' => $projectId]);

        ProjectCCIStatistics::where('project_id', $projectId)->delete();

        Log::info('CCI Statistics deleted successfully', ['project_id' => $projectId]);

        return redirect()->route('projects.edit', $projectId)->with('success', 'Statistics deleted successfully.');
    }
}
