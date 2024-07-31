<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Log;

class KeyInformationController extends Controller
{
    public function store(Request $request, Project $project)
    {
        Log::info('KeyInformationController@store - Data received from form', $request->all());

        $validated = $request->validate([
            'goal' => 'required|string',
        ]);

        try {
            $project->goal = $validated['goal'];
            $project->save();

            Log::info('KeyInformationController@store - Data passed to database', $project->toArray());

            return $project;
        } catch (\Exception $e) {
            Log::error('KeyInformationController@store - Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(Request $request, Project $project)
    {
        Log::info('KeyInformationController@update - Data received from form', $request->all());

        $validated = $request->validate([
            'goal' => 'required|string',
        ]);

        try {
            $project->goal = $validated['goal'];
            $project->save();

            Log::info('KeyInformationController@update - Data passed to database', $project->toArray());

            return $project;
        } catch (\Exception $e) {
            Log::error('KeyInformationController@update - Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
