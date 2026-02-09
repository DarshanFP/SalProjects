<?php

namespace App\Http\Controllers\Projects\CCI;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\CCI\ProjectCCIAgeProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Projects\CCI\StoreCCIAgeProfileRequest;
use App\Http\Requests\Projects\CCI\UpdateCCIAgeProfileRequest;
use Illuminate\Support\Facades\Validator;

class AgeProfileController extends Controller
{
    public function store(FormRequest $request, $projectId)
    {
        $formRequest = StoreCCIAgeProfileRequest::createFrom($request);
        $normalized = $formRequest->getNormalizedInput();
        $validator = Validator::make($normalized, $formRequest->rules());
        $validator->validate();
        $validated = $validator->validated();

        Log::info('Storing CCI Age Profile', ['project_id' => $projectId]);

        $ageProfile = new ProjectCCIAgeProfile();
        $ageProfile->project_id = $projectId;
        $ageProfile->fill($validated);
        $ageProfile->save();

        Log::info('CCI Age Profile created successfully', ['project_id' => $projectId]);

        return redirect()->route('projects.edit', $projectId)->with('success', 'Age Profile created successfully');
    }

    public function update(FormRequest $request, $projectId)
    {
        $formRequest = UpdateCCIAgeProfileRequest::createFrom($request);
        $normalized = $formRequest->getNormalizedInput();
        $validator = Validator::make($normalized, $formRequest->rules());
        $validator->validate();
        $validated = $validator->validated();

        Log::info('Updating or Creating CCI Age Profile', ['project_id' => $projectId]);

        ProjectCCIAgeProfile::updateOrCreate(
            ['project_id' => $projectId],
            $validated
        );

        Log::info('CCI Age Profile updated or created successfully', ['project_id' => $projectId]);

        return redirect()->route('projects.edit', $projectId)->with('success', 'Age Profile updated successfully');
    }

    public function show($projectId)
    {
        try {
            Log::info('Fetching CCI Age Profile', ['project_id' => $projectId]);

            $ageProfile = ProjectCCIAgeProfile::where('project_id', $projectId)->first();

            if ($ageProfile) {
                $ageProfile = $ageProfile->toArray();
            } else {
                Log::warning('No Age Profile found for project', ['project_id' => $projectId]);
                $ageProfile = [
                    'education_below_5_bridge_course_prev_year' => null,
                    'education_below_5_bridge_course_current_year' => null,
                    'education_below_5_kindergarten_prev_year' => null,
                    'education_below_5_kindergarten_current_year' => null,
                    'education_below_5_other_specify' => null,
                    'education_below_5_other_prev_year' => null,
                    'education_below_5_other_current_year' => null,
                ];
            }

            return $ageProfile;
        } catch (\Exception $e) {
            Log::error('Error fetching CCI Age Profile', ['project_id' => $projectId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function edit($projectId)
    {
        try {
            Log::info('Editing CCI Age Profile', ['project_id' => $projectId]);

            $ageProfile = ProjectCCIAgeProfile::where('project_id', $projectId)->first();

            return $ageProfile;
        } catch (\Exception $e) {
            Log::error('Error editing CCI Age Profile', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function destroy($projectId)
    {
        Log::info('Deleting CCI Age Profile', ['project_id' => $projectId]);

        $ageProfile = ProjectCCIAgeProfile::where('project_id', $projectId)->firstOrFail();
        $ageProfile->delete();

        Log::info('CCI Age Profile deleted successfully', ['project_id' => $projectId]);

        return redirect()->route('projects.edit', $projectId)->with('success', 'Age Profile deleted successfully');
    }
}
