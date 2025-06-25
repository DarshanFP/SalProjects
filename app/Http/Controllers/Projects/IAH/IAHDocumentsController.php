<?php

namespace App\Http\Controllers\Projects\IAH;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IAH\ProjectIAHDocuments;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class IAHDocumentsController extends Controller
{
    /**
     * STORE: handle initial file uploads for IAH Documents.
     * Uses ProjectIAHDocuments::handleDocuments($request, $projectId).
     */
    public function store(Request $request, $projectId)
    {
        Log::info('IAHDocumentsController@store - Start', [
            'project_id'   => $projectId,
            'request_data' => $request->all(),
        ]);

        DB::beginTransaction();
        try {
            // (Optional) Validate the presence and types of your file inputs.
            // Make sure the array keys match your Blade: attachments[aadhar_copy], etc.
            $request->validate([
                'attachments.aadhar_copy'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'attachments.request_letter'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'attachments.medical_reports' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'attachments.other_docs'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            ]);

            // Ensure the project exists
            if (!Project::where('project_id', $projectId)->exists()) {
                return response()->json(['error' => 'Project not found.'], 404);
            }

            // The model's static handleDocuments(...) does the actual file moving + DB updates
            $documents = ProjectIAHDocuments::handleDocuments($request, $projectId);

            DB::commit();
            Log::info('IAHDocumentsController@store - Success', [
                'project_id' => $projectId,
                'doc_id'     => $documents->IAH_doc_id ?? null,
            ]);

            return response()->json([
                'message'   => 'IAH documents stored successfully.',
                'documents' => $documents
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHDocumentsController@store - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to store IAH documents.'], 500);
        }
    }

    /**
     * SHOW: retrieve the existing IAHDocuments row and return file URLs, etc.
     */
    public function show($projectId)
    {
        Log::info('IAHDocumentsController@show - Start', ['project_id' => $projectId]);

        try {
            $documents = ProjectIAHDocuments::where('project_id', $projectId)->first();

            // Return the model object directly, not a JSON response
            return $documents;
        } catch (\Exception $e) {
            Log::error('IAHDocumentsController@show - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage()
            ]);
            return null; // Return null instead of JSON error
        }
    }

    /**
     * EDIT: commonly returns either the raw data or a blade partial for editing.
     */
    // public function edit($projectId)
    // {
    //     Log::info('IAHDocumentsController@edit - Start', ['project_id' => $projectId]);

    //     try {
    //         // Eager load the project + doc relationship
    //         $project = Project::where('project_id', $projectId)
    //             ->with('iahDocuments')
    //             ->firstOrFail();

    //         if ($project->iahDocuments) {
    //             Log::info('IAHDocumentsController@edit - Documents found', [
    //                 'doc_id' => $project->iahDocuments->IAH_doc_id
    //             ]);
    //         } else {
    //             Log::warning('IAHDocumentsController@edit - No documents found', [
    //                 'project_id' => $projectId
    //             ]);
    //         }

    //         // Return a blade partial or JSON as needed
    //         return view('projects.partials.Edit.IAH.documents', compact('project'));
    //     } catch (\Exception $e) {
    //         Log::error('IAHDocumentsController@edit - Error', [
    //             'project_id' => $projectId,
    //             'error'      => $e->getMessage()
    //         ]);
    //         return response()->json(['error' => 'Failed to load IAH documents for editing.'], 500);
    //     }
    // }

    public function edit($projectId)
{
    Log::info('IAHDocumentsController@edit - Start', ['project_id' => $projectId]);

    try {
        // Load the project + all IAHDocuments (since hasMany)
        $project = Project::where('project_id', $projectId)
            ->with('iahDocuments')
            ->firstOrFail();

        // If we want to see how many doc records exist:
        if ($project->iahDocuments->isNotEmpty()) {
            Log::info('IAHDocumentsController@edit - Documents found', [
                'count' => $project->iahDocuments->count()
            ]);
        } else {
            Log::warning('IAHDocumentsController@edit - No documents found', [
                'project_id' => $projectId
            ]);
        }

        // Return a Blade partial or full view that includes the existing doc paths:
        return view('projects.partials.Edit.IAH.documents', compact('project'));
    } catch (\Exception $e) {
        Log::error('IAHDocumentsController@edit - Error', [
            'project_id' => $projectId,
            'error'      => $e->getMessage()
        ]);
        return response()->json(['error' => 'Failed to load IAH documents for editing.'], 500);
    }
}


    /**
     * UPDATE: handle new file uploads that overwrite existing files, if present.
     */
    public function update(Request $request, $projectId)
    {
        Log::info('IAHDocumentsController@update - Start', [
            'project_id'   => $projectId,
            'request_data' => $request->all()
        ]);

        DB::beginTransaction();
        try {
            // Validate (optional)
            $request->validate([
                'attachments.aadhar_copy'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'attachments.request_letter'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'attachments.medical_reports' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'attachments.other_docs'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            ]);

            // Overwrites old files if new ones are uploaded for each field
            $documents = ProjectIAHDocuments::handleDocuments($request, $projectId);

            DB::commit();
            Log::info('IAHDocumentsController@update - Success', [
                'project_id' => $projectId,
                'doc_id'     => $documents->IAH_doc_id ?? null
            ]);

            return response()->json([
                'message'   => 'IAH documents updated successfully.',
                'documents' => $documents
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHDocumentsController@update - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to update IAH documents.'], 500);
        }
    }

    /**
     * DESTROY: remove the IAHDocuments record (and its stored files).
     */
    public function destroy($projectId)
    {
        Log::info('IAHDocumentsController@destroy - Start', ['project_id' => $projectId]);

        DB::beginTransaction();
        try {
            $documents = ProjectIAHDocuments::where('project_id', $projectId)->firstOrFail();

            Log::info('IAHDocumentsController@destroy - Deleting record', [
                'doc_id' => $documents->IAH_doc_id
            ]);

            // delete() will also call $documents->deleteAttachments() due to the model's boot() method
            $documents->delete();

            DB::commit();
            Log::info('IAHDocumentsController@destroy - Success', ['project_id' => $projectId]);

            return response()->json(['message' => 'IAH documents deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHDocumentsController@destroy - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to delete IAH documents.'], 500);
        }
    }
}
