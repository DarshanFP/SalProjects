<?php

namespace App\Http\Controllers\Projects\IIES;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\IIES\ProjectIIESAttachments;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Projects\IIES\StoreIIESAttachmentsRequest;
use App\Http\Requests\Projects\IIES\UpdateIIESAttachmentsRequest;

class IIESAttachmentsController extends Controller
{
    /**
     * STORE: handle initial file uploads for IIES Attachments.
     */
    public function store(FormRequest $request, $projectId)
    {
        // Use all() to get all form data including fields not in StoreProjectRequest/UpdateProjectRequest validation rules
        $validated = $request->all();
        
        Log::info('IIESAttachmentsController@store - Start', [
            'project_id' => $projectId
        ]);

        DB::beginTransaction();
        try {

            // Ensure project exists
            if (!Project::where('project_id', $projectId)->exists()) {
                return response()->json(['error' => 'Project not found.'], 404);
            }

            // Model’s static handleAttachments(...) does the actual file + DB work
            $attachments = ProjectIIESAttachments::handleAttachments($request, $projectId);

            DB::commit();
            Log::info('IIESAttachmentsController@store - Success', [
                'project_id' => $projectId,
                'attachment_id' => $attachments->IIES_attachment_id ?? null,
            ]);

            return response()->json([
                'message' => 'IIES attachments stored successfully.',
                'attachments' => $attachments
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IIESAttachmentsController@store - Error', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to store IIES attachments.'], 500);
        }
    }

    /**
     * SHOW: retrieve existing attachments for a project.
     */
    public function show($projectId)
    {
        try {
            Log::info('IIESAttachmentsController@show - Fetching attachments', ['project_id' => $projectId]);

            // Fetch attachments for the given project ID with files relationship
            $attachments = ProjectIIESAttachments::where('project_id', $projectId)
                ->with('files')
                ->first();

            if (!$attachments) {
                Log::warning('IIESAttachmentsController@show - No attachments found', ['project_id' => $projectId]);
                return null; // Return null so Blade can handle it properly
            }

            // Return the attachment object (views will use getFilesForField method)
            return $attachments;
        } catch (\Exception $e) {
            Log::error('IIESAttachmentsController@show - Error retrieving attachments', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);

            return null; // Return null to prevent errors in Blade template
        }
    }


    /**
     * EDIT: return either a Blade view or JSON to allow editing.
     */
    public function edit($projectId)
    {
        Log::info('IIESAttachmentsController@edit - Start', ['project_id' => $projectId]);

        try {
            // Load the project + its IIESAttachments (if any)
            $attachments = ProjectIIESAttachments::where('project_id', $projectId)->first();

            if ($attachments) {
                Log::info('IIESAttachmentsController@edit - Attachments found', [
                    'project_id' => $projectId,
                    'attachment_id' => $attachments->IIES_attachment_id,
                    'stored_files' => $attachments->toArray(), // Logs all stored file paths
                ]);
            } else {
                Log::warning('IIESAttachmentsController@edit - No attachments found', ['project_id' => $projectId]);
            }

            // Log what data is being sent to the main controller
            Log::info('IIESAttachmentsController@edit - Data sent to ProjectController', [
                'attachments' => $attachments ? $attachments->toArray() : null,
            ]);

            return $attachments;
        } catch (\Exception $e) {
            Log::error('IIESAttachmentsController@edit - Error', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to retrieve IIES attachments.'], 500);
        }
    }


    /**
     * UPDATE: handle new file uploads that overwrite old files, if present.
     */
    public function update(FormRequest $request, $projectId)
    {
        // Use all() to get all form data including fields not in StoreProjectRequest/UpdateProjectRequest validation rules
        $validated = $request->all();
        
        Log::info('IIESAttachmentsController@update - Start', [
            'project_id' => $projectId
        ]);

        DB::beginTransaction();
        try {

            $attachments = ProjectIIESAttachments::handleAttachments($request, $projectId);

            DB::commit();
            Log::info('IIESAttachmentsController@update - Success', [
                'project_id' => $projectId,
                'attachment_id' => $attachments->IIES_attachment_id ?? null
            ]);

            // If you prefer returning JSON:
            return response()->json([
                'message' => 'IIES attachments updated successfully.',
                'attachments' => $attachments
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IIESAttachmentsController@update - Error', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to update IIES attachments.'], 500);
        }
    }

    /**
     * DESTROY: remove the IIESAttachments record (and any stored files).
     */
    public function destroy($projectId)
    {
        Log::info('IIESAttachmentsController@destroy - Start', ['project_id' => $projectId]);

        DB::beginTransaction();
        try {
            $attachments = ProjectIIESAttachments::where('project_id', $projectId)->firstOrFail();

            Log::info('Deleting attachments record', [
                'attachment_id' => $attachments->IIES_attachment_id
            ]);

            // Remove the directory & files from storage
            Storage::deleteDirectory("project_attachments/IIES/{$projectId}");
            $attachments->delete();

            DB::commit();
            return response()->json(['message' => 'IIES attachments deleted successfully.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IIESAttachmentsController@destroy - Error', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to delete IIES attachments.'], 500);
        }
    }

}
