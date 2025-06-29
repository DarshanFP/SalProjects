<?php

namespace App\Http\Controllers\Projects\ILP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\ILP\ProjectILPAttachedDocuments;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttachedDocumentsController extends Controller
{
    // 🟢 STORE DOCUMENTS
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            // Ensure the project exists before proceeding
            if (!Project::where('project_id', $projectId)->exists()) {
                return response()->json(['error' => 'Project not found.'], 404);
            }

            // Adjust validation to match the nested 'attachments' fields
            $validatedData = $request->validate([
                'attachments.aadhar_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'attachments.request_letter_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'attachments.purchase_quotation_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'attachments.other_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ]);

            // Log which files exist
            Log::info('Files received in request:', [
                'aadhar_doc' => $request->hasFile('attachments.aadhar_doc'),
                'request_letter_doc' => $request->hasFile('attachments.request_letter_doc'),
                'purchase_quotation_doc' => $request->hasFile('attachments.purchase_quotation_doc'),
                'other_doc' => $request->hasFile('attachments.other_doc'),
            ]);

            // Handle the documents
            ProjectILPAttachedDocuments::handleDocuments($request, $projectId);

            DB::commit();
            Log::info('ILP Attached Documents saved successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Attached Documents saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving ILP Attached Documents', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to save Attached Documents.'], 500);
        }
    }

    // 🟠 SHOW DOCUMENTS
    // public function show($projectId)
    // {
    //     try {
    //         $documents = ProjectILPAttachedDocuments::where('project_id', $projectId)->firstOrFail();

    //         $fields = ['aadhar_doc', 'request_letter_doc', 'purchase_quotation_doc', 'other_doc'];

    //         $documentPaths = [];
    //         foreach ($fields as $field) {
    //             if (!empty($documents->$field)) {
    //                 // Use the model's helper function to get file URLs
    //                 $documentPaths[$field] = $documents->getFileUrl($field);
    //             }
    //         }

    //         return response()->json([
    //             'project_id' => $projectId,
    //             'documents' => $documentPaths,
    //         ], 200);
    //     } catch (\Exception $e) {
    //         Log::error('Error fetching ILP Attached Documents', [
    //             'project_id' => $projectId,
    //             'error' => $e->getMessage(),
    //         ]);

    //         return response()->json(['error' => 'Failed to fetch Attached Documents.'], 500);
    //     }
    // }
    public function show($projectId)
{
    try {
        Log::info('Fetching ILP Attached Documents', ['project_id' => $projectId]);

        $documents = ProjectILPAttachedDocuments::where('project_id', $projectId)->first();

        return [
            'aadhar_doc' => $documents && $documents->aadhar_doc ? Storage::url($documents->aadhar_doc) : null,
            'request_letter_doc' => $documents && $documents->request_letter_doc ? Storage::url($documents->request_letter_doc) : null,
            'purchase_quotation_doc' => $documents && $documents->purchase_quotation_doc ? Storage::url($documents->purchase_quotation_doc) : null,
            'other_doc' => $documents && $documents->other_doc ? Storage::url($documents->other_doc) : null,
        ];
    } catch (\Exception $e) {
        Log::error('Error fetching ILP Attached Documents', ['error' => $e->getMessage()]);
        return [
            'aadhar_doc' => null,
            'request_letter_doc' => null,
            'purchase_quotation_doc' => null,
            'other_doc' => null,
        ];
    }
}


    // 🟡 EDIT DOCUMENTS (LOAD EDIT PARTIAL)
    public function edit($projectId)
    {
        try {
            Log::info('Fetching ILP attached documents for editing', ['project_id' => $projectId]);

            // Fetch the attached documents for the given project ID
            $documents = ProjectILPAttachedDocuments::where('project_id', $projectId)->first();

            // Ensure documents exist
            if (!$documents) {
                Log::warning('No ILP Attached Documents found for Edit', ['project_id' => $projectId]);
                return null; // Return null for Blade to handle gracefully
            }

            // ✅ Return the raw Eloquent object (Same as IES Controller)
            return $documents;
        } catch (\Exception $e) {
            Log::error('Error fetching ILP Attached Documents for editing', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);

            return null; // Prevents errors in Blade template if no data exists
        }
    }



    // 🟢 UPDATE DOCUMENTS
    public function update(Request $request, $projectId)
{
    DB::beginTransaction();
    try {
        Log::info('Updating ILP Attached Documents', ['project_id' => $projectId]);

        // Validate request inputs
        $validatedData = $request->validate([
            'attachments.aadhar_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'attachments.request_letter_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'attachments.purchase_quotation_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'attachments.other_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        // Call the new handleDocuments with extra logging
        $documents = ProjectILPAttachedDocuments::handleDocuments($request, $projectId);

        DB::commit();
        Log::info('ILP Attached Documents updated successfully', [
            'project_id' => $projectId,
            'paths_in_db' => $documents->only(['aadhar_doc','request_letter_doc','purchase_quotation_doc','other_doc'])
        ]);

        return response()->json(['message' => 'Attached Documents updated successfully.'], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error updating ILP Attached Documents', [
            'project_id' => $projectId,
            'error' => $e->getMessage(),
        ]);

        return response()->json(['error' => 'Failed to update Attached Documents.'], 500);
    }
}



    // 🔵 DELETE DOCUMENTS
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            $documents = ProjectILPAttachedDocuments::where('project_id', $projectId)->firstOrFail();

            if ($documents) {
                // Delete associated files
                $documents->deleteAttachments();

                // Delete the database record
                $documents->delete();

                // Delete the directory if it is now empty
                $projectDir = "public/project_attachments/ILP/{$projectId}";
                if (Storage::exists($projectDir) && empty(Storage::files($projectDir))) {
                    Storage::deleteDirectory($projectDir);
                    Log::info("Deleted empty directory for project", ['directory' => $projectDir]);
                }
            }

            DB::commit();
            return response()->json(['message' => 'Attached Documents deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting ILP Attached Documents', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to delete Attached Documents.'], 500);
        }
    }
}
