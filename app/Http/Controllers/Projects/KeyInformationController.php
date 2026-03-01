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
                Log::info('KeyInformationController@store - Problem tree image file detected', [
                    'project_id' => $project->project_id,
                    'has_old_path' => !empty($project->problem_tree_file_path),
                    'old_path' => $project->problem_tree_file_path,
                ]);
                
                $this->storeProblemTreeImage($request, $project);
                
                Log::info('KeyInformationController@store - After storeProblemTreeImage', [
                    'project_id' => $project->project_id,
                    'new_path' => $project->problem_tree_file_path,
                    'path_changed' => $project->isDirty('problem_tree_file_path'),
                ]);
            }

            Log::info('KeyInformationController@store - About to save project', [
                'project_id' => $project->project_id,
                'problem_tree_file_path' => $project->problem_tree_file_path,
                'dirty_attributes' => $project->getDirty(),
            ]);

            $project->save();

            Log::info('KeyInformationController@store - Data saved successfully', [
                'project_id' => $project->project_id,
                'problem_tree_file_path' => $project->problem_tree_file_path,
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
                Log::info('KeyInformationController@update - Problem tree image file detected', [
                    'project_id' => $project->project_id,
                    'has_old_path' => !empty($project->problem_tree_file_path),
                    'old_path' => $project->problem_tree_file_path,
                ]);
                
                $this->storeProblemTreeImage($request, $project);
                
                Log::info('KeyInformationController@update - After storeProblemTreeImage', [
                    'project_id' => $project->project_id,
                    'new_path' => $project->problem_tree_file_path,
                    'path_changed' => $project->isDirty('problem_tree_file_path'),
                ]);
            }

        Log::info('KeyInformationController@update - About to save project', [
            'project_id' => $project->project_id,
            'problem_tree_file_path' => $project->problem_tree_file_path,
            'dirty_attributes' => $project->getDirty(),
        ]);

        $project->save();

            Log::info('KeyInformationController@update - Data saved successfully', [
            'project_id' => $project->project_id,
            'problem_tree_file_path' => $project->problem_tree_file_path,
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

        // Store old path for cleanup AFTER successful write
        $oldPath = $project->problem_tree_file_path;

        Log::info('ProblemTreeImage - Starting upload process', [
            'project_id' => $project->project_id,
            'folder' => $folder,
            'uploaded_filename' => $file->getClientOriginalName(),
            'uploaded_size' => $file->getSize(),
            'uploaded_mime' => $file->getMimeType(),
            'old_path' => $oldPath,
        ]);

        // NOTE: We do NOT delete the old file here anymore
        // We'll delete it AFTER the new file is successfully written
        if ($oldPath && $disk->exists($oldPath)) {
            Log::info('ProblemTreeImage - Old file exists (will delete after new file written)', [
                'project_id' => $project->project_id,
                'old_path' => $oldPath,
            ]);
        } else {
            Log::info('ProblemTreeImage - No old file to cleanup', [
                'project_id' => $project->project_id,
                'old_path' => $oldPath,
            ]);
        }

        $service = app(ProblemTreeImageService::class);
        
        Log::info('ProblemTreeImage - Starting optimization', [
            'project_id' => $project->project_id,
        ]);
        
        $optimized = $service->optimize($file);

        Log::info('ProblemTreeImage - Optimization completed', [
            'project_id' => $project->project_id,
            'optimized' => $optimized !== null,
            'optimized_size' => $optimized !== null ? strlen($optimized) : null,
        ]);

        if ($optimized !== null) {
            // Use timestamp to create unique filename and avoid browser caching
            $timestamp = now()->format('YmdHis');
            $filename = $project->project_id . '_Problem_Tree_' . $timestamp . '.jpg';
            $path = $folder . '/' . $filename;

            Log::info('ProblemTreeImage - Using optimized image path', [
                'project_id' => $project->project_id,
                'path' => $path,
                'filename' => $filename,
                'folder_exists_before' => $disk->exists($folder),
            ]);

            if (!$disk->exists($folder)) {
                Log::info('ProblemTreeImage - Creating directory', [
                    'project_id' => $project->project_id,
                    'folder' => $folder,
                ]);

                $makeDir = $disk->makeDirectory($folder);

                Log::info('ProblemTreeImage - Directory creation result', [
                    'project_id' => $project->project_id,
                    'folder' => $folder,
                    'result' => $makeDir,
                    'folder_exists_after' => $disk->exists($folder),
                ]);
            }

            Log::info('ProblemTreeImage - About to write optimized file', [
                'project_id' => $project->project_id,
                'path' => $path,
                'size' => strlen($optimized),
                'file_exists_before_write' => $disk->exists($path),
            ]);

            $putResult = $disk->put($path, $optimized);

            Log::info('ProblemTreeImage - Write operation completed', [
                'project_id' => $project->project_id,
                'path' => $path,
                'put_result' => $putResult,
                'file_exists_after_write' => $disk->exists($path),
                'file_size_after_write' => $disk->exists($path) ? $disk->size($path) : null,
            ]);

            $project->problem_tree_file_path = $path;

            Log::info('ProblemTreeImage - Model updated with new path', [
                'project_id' => $project->project_id,
                'path' => $path,
            ]);
        } else {
            $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?? 'jpg');
            if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $ext = 'jpg';
            }
            // Use timestamp to create unique filename and avoid browser caching
            $timestamp = now()->format('YmdHis');
            $filename = $project->project_id . '_Problem_Tree_' . $timestamp . '.' . $ext;

            Log::info('ProblemTreeImage - Using original file (optimization disabled/failed)', [
                'project_id' => $project->project_id,
                'filename' => $filename,
                'folder' => $folder,
                'extension' => $ext,
                'folder_exists_before' => $disk->exists($folder),
            ]);

            $path = $file->storeAs($folder, $filename, 'public');

            Log::info('ProblemTreeImage - storeAs completed', [
                'project_id' => $project->project_id,
                'path' => $path,
                'storeAs_result' => $path !== false,
                'file_exists_after' => $path !== false && $disk->exists($path),
                'file_size_after' => ($path !== false && $disk->exists($path)) ? $disk->size($path) : null,
            ]);

            $project->problem_tree_file_path = $path;

            Log::info('ProblemTreeImage - Model updated with original file path', [
                'project_id' => $project->project_id,
                'path' => $path,
            ]);
        }

        Log::info('ProblemTreeImage - Process completed successfully', [
            'project_id' => $project->project_id,
            'final_path' => $project->problem_tree_file_path,
            'final_file_exists' => $disk->exists($project->problem_tree_file_path),
            'final_file_size' => $disk->exists($project->problem_tree_file_path) ? $disk->size($project->problem_tree_file_path) : null,
        ]);

        // NOW delete the old file after the new one is confirmed written
        // This prevents data loss if the write fails
        if ($oldPath && $oldPath !== $project->problem_tree_file_path && $disk->exists($oldPath)) {
            Log::info('ProblemTreeImage - Deleting old file after successful write', [
                'project_id' => $project->project_id,
                'old_path' => $oldPath,
                'new_path' => $project->problem_tree_file_path,
            ]);

            $deleted = $disk->delete($oldPath);

            Log::info('ProblemTreeImage - Old file cleanup completed', [
                'project_id' => $project->project_id,
                'old_path' => $oldPath,
                'deleted' => $deleted,
                'still_exists' => $disk->exists($oldPath),
            ]);
        }
    }
}
