<?php

namespace App\Http\Controllers\Projects\IAH;

use App\Constants\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Helpers\ProjectPermissionHelper;
use App\Models\OldProjects\IAH\ProjectIAHDocuments;
use App\Models\OldProjects\IAH\ProjectIAHDocumentFile;
use App\Models\OldProjects\Project;
use App\Services\Attachment\AttachmentContext;
use App\Services\ProjectAttachmentHandler;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class IAHDocumentsController extends Controller
{
    private const IAH_FIELDS = [
        'aadhar_copy',
        'request_letter',
        'medical_reports',
        'other_docs',
    ];

    /** @return array<string, array> */
    private static function iahFieldConfig(): array
    {
        return array_fill_keys(self::IAH_FIELDS, []);
    }

    /**
     * STORE: handle initial file uploads for IAH Documents.
     */
    public function store(FormRequest $request, $projectId)
    {
        Log::info('IAHDocumentsController@store - Start', [
            'project_id' => $projectId
        ]);

        DB::beginTransaction();
        try {
            if (!Project::where('project_id', $projectId)->exists()) {
                return response()->json(['error' => 'Project not found.'], 404);
            }

            // M1 Data Integrity Shield: skip when no files uploaded.
            if (! $this->hasAnyIAHFile($request)) {
                Log::info('IAHDocumentsController@store - No files uploaded; skipping mutation', [
                    'project_id' => $projectId,
                ]);

                DB::commit();

                return response()->json([
                    'message' => 'IAH documents stored successfully.',
                    'documents' => null,
                ], 200);
            }

            $result = ProjectAttachmentHandler::handle(
                $request,
                (string) $projectId,
                AttachmentContext::forIAH(),
                self::iahFieldConfig()
            );

            if (!$result->success) {
                DB::rollBack();
                Log::warning('IAH documents validation failed', [
                    'project_id' => $projectId,
                    'errors' => $result->errorsByField,
                ]);
                return response()->json([
                    'error' => 'Failed to store IAH documents.',
                    'errors' => $result->errorsByField,
                ], 422);
            }

            DB::commit();
            Log::info('IAHDocumentsController@store - Success', [
                'project_id' => $projectId,
                'doc_id' => $result->attachmentRecord->IAH_doc_id ?? null,
            ]);

            return response()->json([
                'message' => 'IAH documents stored successfully.',
                'documents' => $result->attachmentRecord,
            ], 200);

        } catch (\Throwable $e) {
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
        Log::info('IAHDocumentsController@update - Start', [
            'project_id' => $projectId
        ]);

        DB::beginTransaction();
        try {
            // M1 Data Integrity Shield: skip when no files uploaded.
            if (! $this->hasAnyIAHFile($request)) {
                Log::info('IAHDocumentsController@update - No files uploaded; skipping mutation', [
                    'project_id' => $projectId,
                ]);

                DB::commit();

                return response()->json([
                    'message' => 'IAH documents updated successfully.',
                    'documents' => null,
                ], 200);
            }

            $result = ProjectAttachmentHandler::handle(
                $request,
                (string) $projectId,
                AttachmentContext::forIAH(),
                self::iahFieldConfig()
            );

            if (!$result->success) {
                DB::rollBack();
                Log::warning('IAH documents validation failed on update', [
                    'project_id' => $projectId,
                    'errors' => $result->errorsByField,
                ]);
                return response()->json([
                    'error' => 'Failed to update IAH documents.',
                    'errors' => $result->errorsByField,
                ], 422);
            }

            DB::commit();
            Log::info('IAHDocumentsController@update - Success', [
                'project_id' => $projectId,
                'doc_id' => $result->attachmentRecord->IAH_doc_id ?? null
            ]);

            return response()->json([
                'message' => 'IAH documents updated successfully.',
                'documents' => $result->attachmentRecord,
            ], 200);

        } catch (\Throwable $e) {
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

    private function hasAnyIAHFile(Request $request): bool
    {
        $fileFields = array_keys(self::iahFieldConfig());

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * DOWNLOAD: download a specific IAH document file
     * Streams file through controller - works without storage symlink on production.
     * Guard: province isolation, canView — read allowed even when project is approved (403 on failure).
     */
    public function downloadFile($fileId)
    {
        try {
            Log::info('IAHDocumentsController@downloadFile - Start', ['file_id' => $fileId]);

            $file = ProjectIAHDocumentFile::findOrFail($fileId);

            $project = $file->project ?? $file->iahDocument?->project;
            if (! $project) {
                Log::warning('IAHDocumentsController@downloadFile - No project for file', ['file_id' => $fileId]);
                return response()->json(['error' => 'File record not found'], 404);
            }

            $user = Auth::user();
            if (! ProjectPermissionHelper::passesProvinceCheck($project, $user)) {
                abort(403);
            }
            if (! ProjectPermissionHelper::canView($project, $user)) {
                abort(403);
            }

            Log::info('IAHDocumentsController@downloadFile - File found', [
                'file_id' => $fileId,
                'file_path' => $file->file_path,
                'file_name' => $file->file_name,
            ]);

            if (!Storage::disk('public')->exists($file->file_path)) {
                Log::error('IAHDocumentsController@downloadFile - File not found on disk', [
                    'file_id' => $fileId,
                    'file_path' => $file->file_path,
                ]);
                return response()->json(['error' => 'File not found'], 404);
            }

            Log::info('IAHDocumentsController@downloadFile - File downloaded', [
                'file_id' => $fileId,
                'file_name' => $file->file_name,
            ]);

            return Storage::disk('public')->download($file->file_path, $file->file_name);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('IAHDocumentsController@downloadFile - File record not found', ['file_id' => $fileId]);
            return response()->json(['error' => 'File record not found'], 404);
        } catch (\Exception $e) {
            Log::error('IAHDocumentsController@downloadFile - Error', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to download file'], 500);
        }
    }

    /**
     * VIEW: view a specific IAH document file (stream response)
     * Streams file through controller - works without storage symlink on production.
     * Guard: province isolation, canView — read allowed even when project is approved (403 on failure).
     */
    public function viewFile($fileId)
    {
        try {
            Log::info('IAHDocumentsController@viewFile - Start', ['file_id' => $fileId]);

            $file = ProjectIAHDocumentFile::findOrFail($fileId);

            $project = $file->project ?? $file->iahDocument?->project;
            if (! $project) {
                Log::warning('IAHDocumentsController@viewFile - No project for file', ['file_id' => $fileId]);
                return response()->json(['error' => 'File record not found'], 404);
            }

            $user = Auth::user();
            if (! ProjectPermissionHelper::passesProvinceCheck($project, $user)) {
                abort(403);
            }
            if (! ProjectPermissionHelper::canView($project, $user)) {
                abort(403);
            }

            Log::info('IAHDocumentsController@viewFile - File found', [
                'file_id' => $fileId,
                'file_path' => $file->file_path,
                'file_name' => $file->file_name,
            ]);

            if (!Storage::disk('public')->exists($file->file_path)) {
                Log::error('IAHDocumentsController@viewFile - File not found on disk', [
                    'file_id' => $fileId,
                    'file_path' => $file->file_path,
                ]);
                return response()->json(['error' => 'File not found'], 404);
            }

            $fileContent = Storage::disk('public')->get($file->file_path);
            $mimeType = Storage::disk('public')->mimeType($file->file_path);

            Log::info('IAHDocumentsController@viewFile - File viewed', [
                'file_id' => $fileId,
                'file_name' => $file->file_name,
                'mime_type' => $mimeType,
            ]);

            return response($fileContent, 200)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'inline; filename="' . $file->file_name . '"');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('IAHDocumentsController@viewFile - File record not found', ['file_id' => $fileId]);
            return response()->json(['error' => 'File record not found'], 404);
        } catch (\Exception $e) {
            Log::error('IAHDocumentsController@viewFile - Error', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to view file'], 500);
        }
    }

    /**
     * PER-FILE DELETE: remove one IAH document file.
     * Mutation: enforces province, editable status, canEdit. Model deleting event removes storage.
     */
    public function destroyFile($fileId)
    {
        try {
            Log::info('IAHDocumentsController@destroyFile - Start', [
                'file_id' => $fileId,
                'user_id' => Auth::id(),
            ]);

            $file = ProjectIAHDocumentFile::findOrFail($fileId);

            $project = $file->project ?? $file->iahDocument?->project;
            if (! $project) {
                return response()->json(['error' => 'File record not found'], 404);
            }

            $user = Auth::user();
            if (! ProjectPermissionHelper::passesProvinceCheck($project, $user)) {
                Log::warning('IAH Delete Blocked - province_mismatch', [
                    'file_id' => $fileId,
                    'user_id' => Auth::id(),
                    'project_id' => $project->project_id ?? $project->id ?? null,
                    'reason' => 'province_mismatch',
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden.',
                ], 403);
            }
            if (! ProjectStatus::isEditable($project->status)) {
                Log::warning('IAH Delete Blocked - not_editable', [
                    'file_id' => $fileId,
                    'user_id' => Auth::id(),
                    'project_id' => $project->project_id ?? $project->id ?? null,
                    'reason' => 'not_editable',
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden.',
                ], 403);
            }
            if (! ProjectPermissionHelper::canEdit($project, $user)) {
                Log::warning('IAH Delete Blocked - cannot_edit', [
                    'file_id' => $fileId,
                    'user_id' => Auth::id(),
                    'project_id' => $project->project_id ?? $project->id ?? null,
                    'reason' => 'cannot_edit',
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden.',
                ], 403);
            }

            $file->delete();

            Log::info('IAHDocumentsController@destroyFile - Success', [
                'file_id' => $fileId,
                'project_id' => $project->project_id ?? $project->id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully.',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'File record not found'], 404);
        } catch (\Exception $e) {
            Log::error('IAHDocumentsController@destroyFile - Error', [
                'file_id' => $fileId ?? null,
                'user_id' => Auth::id(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Server error during delete.',
            ], 500);
        }
    }

}
