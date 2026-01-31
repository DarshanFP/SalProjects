<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Services\ProblemTreeImageService;
use Illuminate\Http\Request;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
            'economic_situation' => 'nullable|string',
            'goal' => 'nullable|string',
            'problem_tree_image' => 'nullable|file|image|mimes:jpeg,jpg,png|max:7168',
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
            if (array_key_exists('economic_situation', $validated)) {
                $project->economic_situation = $validated['economic_situation'];
            }
            if (array_key_exists('goal', $validated)) {
                $project->goal = $validated['goal'];
            }

            // Problem Tree image (one per project; new upload replaces previous)
            if ($request->hasFile('problem_tree_image')) {
                $this->storeProblemTreeImage($request, $project);
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
            'economic_situation' => 'nullable|string',
            'goal' => 'nullable|string',
            'problem_tree_image' => 'nullable|file|image|mimes:jpeg,jpg,png|max:7168',
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
            if (array_key_exists('economic_situation', $validated)) {
                $project->economic_situation = $validated['economic_situation'];
            }
        if (array_key_exists('goal', $validated)) {
            $project->goal = $validated['goal'];
        }

            // Problem Tree image (one per project; new upload replaces previous)
            if ($request->hasFile('problem_tree_image')) {
                $this->storeProblemTreeImage($request, $project);
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

    /**
     * Store or replace the Problem Tree image. One per project; new upload overwrites previous.
     * Images are resized and re-encoded as JPEG to reduce file size when optimization is enabled.
     */
    private function storeProblemTreeImage(Request $request, Project $project): void
    {
        $file = $request->file('problem_tree_image');
        $folder = $project->getAttachmentBasePath();
        $disk = Storage::disk('public');

        // Replace: delete existing file if present
        if ($project->problem_tree_file_path && $disk->exists($project->problem_tree_file_path)) {
            $disk->delete($project->problem_tree_file_path);
        }

        $service = app(ProblemTreeImageService::class);
        $optimized = $service->optimize($file);

        if ($optimized !== null) {
            $filename = $project->project_id . '_Problem_Tree.jpg';
            $path = $folder . '/' . $filename;
            if (!$disk->exists($folder)) {
                $disk->makeDirectory($folder);
            }
            $disk->put($path, $optimized);
            $project->problem_tree_file_path = $path;
        } else {
            $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?? 'jpg');
            if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $ext = 'jpg';
            }
            $filename = $project->project_id . '_Problem_Tree.' . $ext;
            $path = $file->storeAs($folder, $filename, 'public');
            $project->problem_tree_file_path = $path;
        }
    }
}
