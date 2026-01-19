<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Log;

class KeyInformationController extends Controller
{
    /**
     * Create/initialize key information for a project
     * Note: goal is now nullable, so no initialization needed
     */
    public function create(Project $project)
    {
        // goal is now nullable, so no initialization needed
        return $project;
    }

    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'initial_information' => 'nullable|string',
            'target_beneficiaries' => 'nullable|string',
            'general_situation' => 'nullable|string',
            'need_of_project' => 'nullable|string',
            'goal' => 'nullable|string',
        ]);
        
        Log::info('KeyInformationController@store - Data received from form', [
            'project_id' => $project->project_id
        ]);

        try {
            // Update all fields if provided
            if (array_key_exists('initial_information', $validated)) {
                $project->initial_information = $validated['initial_information'];
            }
            if (array_key_exists('target_beneficiaries', $validated)) {
                $project->target_beneficiaries = $validated['target_beneficiaries'];
            }
            if (array_key_exists('general_situation', $validated)) {
                $project->general_situation = $validated['general_situation'];
            }
            if (array_key_exists('need_of_project', $validated)) {
                $project->need_of_project = $validated['need_of_project'];
            }
            if (array_key_exists('goal', $validated)) {
                $project->goal = $validated['goal'];
            }
            
            $project->save();

            Log::info('KeyInformationController@store - Data saved successfully', [
                'project_id' => $project->project_id,
            ]);

            return $project;
        } catch (\Exception $e) {
            Log::error('KeyInformationController@store - Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(Request $request, Project $project)
{
    $validated = $request->validate([
            'initial_information' => 'nullable|string',
            'target_beneficiaries' => 'nullable|string',
            'general_situation' => 'nullable|string',
            'need_of_project' => 'nullable|string',
        'goal' => 'nullable|string',
    ]);
    
    Log::info('KeyInformationController@update - Data received from form', [
        'project_id' => $project->project_id
    ]);

    try {
            // Update all fields if provided
            if (array_key_exists('initial_information', $validated)) {
                $project->initial_information = $validated['initial_information'];
            }
            if (array_key_exists('target_beneficiaries', $validated)) {
                $project->target_beneficiaries = $validated['target_beneficiaries'];
            }
            if (array_key_exists('general_situation', $validated)) {
                $project->general_situation = $validated['general_situation'];
            }
            if (array_key_exists('need_of_project', $validated)) {
                $project->need_of_project = $validated['need_of_project'];
            }
        if (array_key_exists('goal', $validated)) {
            $project->goal = $validated['goal'];
        }
            
        $project->save();

            Log::info('KeyInformationController@update - Data saved successfully', [
            'project_id' => $project->project_id,
        ]);

        return $project;
    } catch (\Exception $e) {
        Log::error('KeyInformationController@update - Error', ['error' => $e->getMessage()]);
        throw $e;
    }
}

}
