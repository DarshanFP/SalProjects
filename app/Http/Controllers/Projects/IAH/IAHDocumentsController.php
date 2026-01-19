<?php

namespace App\Http\Controllers\Projects\IAH;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\IAH\ProjectIAHDocuments;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Projects\IAH\StoreIAHDocumentsRequest;
use App\Http\Requests\Projects\IAH\UpdateIAHDocumentsRequest;

class IAHDocumentsController extends Controller
{
    /**
     * STORE: handle initial file uploads for IAH Documents.
     */
    public function store(FormRequest $request, $projectId)
    {
        // Use all() to get all form data including fields not in StoreProjectRequest/UpdateProjectRequest validation rules
        $validated = $request->all();
        
        Log::info('IAHDocumentsController@store - Start', [
            'project_id' => $projectId
        ]);

        DB::beginTransaction();
        try {

            // Ensure project exists
            if (!Project::where('project_id', $projectId)->exists()) {
                return response()->json(['error' => 'Project not found.'], 404);
            }

            // Model's static handleDocuments(...) does the actual file + DB work
            $documents = ProjectIAHDocuments::handleDocuments($request, $projectId);

            DB::commit();
            Log::info('IAHDocumentsController@store - Success', [
                'project_id' => $projectId,
                'doc_id' => $documents->IAH_doc_id ?? null,
            ]);

            return response()->json([
                'message' => 'IAH documents stored successfully.',
                'documents' => $documents
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHDocumentsController@store - Error', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to store IAH documents.'], 500);
        }
    }

    /**
     * SHOW: retrieve existing documents for a project.
     */
    public function show($projectId)
    {
        try {
            Log::info('IAHDocumentsController@show - Fetching documents', ['project_id' => $projectId]);

            // Fetch documents for the given project ID with files relationship
            $documents = ProjectIAHDocuments::where('project_id', $projectId)
                ->with('files')
                ->first();

            if (!$documents) {
                Log::warning('IAHDocumentsController@show - No documents found', ['project_id' => $projectId]);
                return null; // Return null so Blade can handle it properly
            }

            // Return the document object (views will use getFilesForField method)
            return $documents;
        } catch (\Exception $e) {
            Log::error('IAHDocumentsController@show - Error retrieving documents', [
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
        Log::info('IAHDocumentsController@edit - Start', ['project_id' => $projectId]);

        try {
            // Load the project + its IAH Documents (if any)
            $documents = ProjectIAHDocuments::where('project_id', $projectId)->first();

            if ($documents) {
                Log::info('IAHDocumentsController@edit - Documents found', [
                    'project_id' => $projectId,
                    'doc_id' => $documents->IAH_doc_id,
                    'stored_files' => $documents->toArray(), // Logs all stored file paths
                ]);
            } else {
                Log::warning('IAHDocumentsController@edit - No documents found', ['project_id' => $projectId]);
            }

            // Log what data is being sent to the main controller
            Log::info('IAHDocumentsController@edit - Data sent to ProjectController', [
                'documents' => $documents ? $documents->toArray() : null,
            ]);

            return $documents;
        } catch (\Exception $e) {
            Log::error('IAHDocumentsController@edit - Error', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to retrieve IAH documents.'], 500);
        }
    }

    /**
     * UPDATE: handle new file uploads that overwrite old files, if present.
     */
    public function update(FormRequest $request, $projectId)
    {
        // Use all() to get all form data including fields not in StoreProjectRequest/UpdateProjectRequest validation rules
        $validated = $request->all();
        
        Log::info('IAHDocumentsController@update - Start', [
            'project_id' => $projectId
        ]);

        DB::beginTransaction();
        try {

            $documents = ProjectIAHDocuments::handleDocuments($request, $projectId);

            DB::commit();
            Log::info('IAHDocumentsController@update - Success', [
                'project_id' => $projectId,
                'doc_id' => $documents->IAH_doc_id ?? null
            ]);

            // If you prefer returning JSON:
            return response()->json([
                'message' => 'IAH documents updated successfully.',
                'documents' => $documents
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHDocumentsController@update - Error', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to update IAH documents.'], 500);
        }
    }

    /**
     * DESTROY: remove the IAH Documents record (and any stored files).
     */
    public function destroy($projectId)
    {
        Log::info('IAHDocumentsController@destroy - Start', ['project_id' => $projectId]);

        DB::beginTransaction();
        try {
            $documents = ProjectIAHDocuments::where('project_id', $projectId)->firstOrFail();

            Log::info('Deleting documents record', [
                'doc_id' => $documents->IAH_doc_id
            ]);

            // Remove the directory & files from storage
            Storage::deleteDirectory("project_attachments/IAH/{$projectId}");
            $documents->delete();

            DB::commit();
            return response()->json(['message' => 'IAH documents deleted successfully.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHDocumentsController@destroy - Error', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to delete IAH documents.'], 500);
        }
    }

}
