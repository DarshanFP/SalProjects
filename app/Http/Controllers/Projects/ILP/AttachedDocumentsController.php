<?php

namespace App\Http\Controllers\Projects\ILP;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\ILP\ProjectILPAttachedDocuments;
use App\Models\OldProjects\Project;
use App\Services\Attachment\AttachmentContext;
use App\Services\ProjectAttachmentHandler;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AttachedDocumentsController extends Controller
{
    private const ILP_FIELDS = [
        'aadhar_doc',
        'request_letter_doc',
        'purchase_quotation_doc',
        'other_doc',
    ];

    /** @return array<string, array> */
    private static function ilpFieldConfig(): array
    {
        return array_fill_keys(self::ILP_FIELDS, []);
    }

    // 🟢 STORE DOCUMENTS
    public function store(FormRequest $request, $projectId)
    {
        DB::beginTransaction();
        try {
            if (!Project::where('project_id', $projectId)->exists()) {
                return response()->json(['error' => 'Project not found.'], 404);
            }

            if (! $this->hasAnyILPFile($request)) {
                Log::info('ILPAttachedDocumentsController@store - No files present; skipping mutation', [
                    'project_id' => $projectId,
                ]);

                DB::commit();

                return response()->json([
                    'message' => 'ILP attached documents saved successfully.'
                ], 200);
            }

            $result = ProjectAttachmentHandler::handle(
                $request,
                (string) $projectId,
                AttachmentContext::forILP(),
                self::ilpFieldConfig()
            );

            if (!$result->success) {
                DB::rollBack();
                Log::warning('ILP Attached Documents validation failed', [
                    'project_id' => $projectId,
                    'errors' => $result->errorsByField,
                ]);
                return response()->json([
                    'error' => 'Failed to save Attached Documents.',
                    'errors' => $result->errorsByField,
                ], 422);
            }

            DB::commit();
            Log::info('ILP Attached Documents saved successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Attached Documents saved successfully.'], 200);
        } catch (\Throwable $e) {
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
            
            // Fetch documents for the given project ID with files relationship
            $documents = ProjectILPAttachedDocuments::where('project_id', $projectId)
                ->with('files')
                ->first();

            if (!$documents) {
                Log::warning('ILP AttachedDocumentsController@show - No documents found', ['project_id' => $projectId]);
                return null; // Return null so Blade can handle it properly
            }

            // Return the document object (views will use getFilesForField method)
            return $documents;
        } catch (\Exception $e) {
            Log::error('Error fetching ILP Attached Documents', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);
            return null; // Return null to prevent errors in Blade template
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
    public function update(FormRequest $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Updating ILP Attached Documents', ['project_id' => $projectId]);

            if (! $this->hasAnyILPFile($request)) {
                Log::info('ILPAttachedDocumentsController@update - No files present; skipping mutation', [
                    'project_id' => $projectId,
                ]);

                DB::commit();

                return response()->json([
                    'message' => 'ILP attached documents updated successfully.'
                ], 200);
            }

            $result = ProjectAttachmentHandler::handle(
                $request,
                (string) $projectId,
                AttachmentContext::forILP(),
                self::ilpFieldConfig()
            );

            if (!$result->success) {
                DB::rollBack();
                Log::warning('ILP Attached Documents validation failed on update', [
                    'project_id' => $projectId,
                    'errors' => $result->errorsByField,
                ]);
                return response()->json([
                    'error' => 'Failed to update Attached Documents.',
                    'errors' => $result->errorsByField,
                ], 422);
            }

            DB::commit();
            Log::info('ILP Attached Documents updated successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Attached Documents updated successfully.'], 200);
        } catch (\Throwable $e) {
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

    private function hasAnyILPFile(Request $request): bool
    {
        foreach (self::ILP_FIELDS as $field) {
            if ($request->hasFile($field)) {
                return true;
            }
        }
        return false;
    }
}
