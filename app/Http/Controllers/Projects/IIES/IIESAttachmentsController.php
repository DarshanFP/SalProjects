<?php

namespace App\Http\Controllers\Projects\IIES;

use App\Http\Controllers\Controller;
use App\Helpers\LogHelper;
use App\Models\OldProjects\IIES\ProjectIIESAttachments;
use App\Models\OldProjects\IIES\ProjectIIESAttachmentFile;
use App\Models\OldProjects\Project;
use App\Services\Attachment\AttachmentContext;
use App\Services\ProjectAttachmentHandler;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Projects\IIES\StoreIIESAttachmentsRequest;
use App\Http\Requests\Projects\IIES\UpdateIIESAttachmentsRequest;

class IIESAttachmentsController extends Controller
{
    private const IIES_FIELDS = [
        'iies_aadhar_card', 'iies_fee_quotation', 'iies_scholarship_proof', 'iies_medical_confirmation',
        'iies_caste_certificate', 'iies_self_declaration', 'iies_death_certificate', 'iies_request_letter',
    ];

    /** @return array<string, array> */
    private static function iiesFieldConfig(): array
    {
        return array_fill_keys(self::IIES_FIELDS, []);
    }

    /**
     * STORE: handle initial file uploads for IIES Attachments.
     */
    public function store(FormRequest $request, $projectId)
    {
        Log::info('IIESAttachmentsController@store - Start', [
            'project_id' => $projectId
        ]);

        try {
            // Ensure project exists
            if (!Project::where('project_id', $projectId)->exists()) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Project not found.');
            }
            // Phase 4: Defensive persistence — avoid empty attachment record when no files
            $hasAnyFile = collect(self::IIES_FIELDS)->contains(fn ($field) => $request->hasFile($field));
            if (!$hasAnyFile) {
                Log::info('IIESAttachmentsController@store - Skipping save; no attachment files present', ['project_id' => $projectId]);
                return response()->json(['message' => 'IIES attachments skipped (no files present).'], 200);
            }

            $result = ProjectAttachmentHandler::handle(
                $request,
                (string) $projectId,
                AttachmentContext::forIIES(),
                self::iiesFieldConfig()
            );

            if (!$result->success) {
                Log::warning('IIES attachment validation failed', [
                    'project_id' => $projectId,
                    'errors' => $result->errorsByField,
                ]);
                throw \Illuminate\Validation\ValidationException::withMessages($result->errorsByField);
            }

            return response()->json([
                'message' => 'IIES attachments stored successfully.',
                'attachments' => $result->attachmentRecord
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('IIESAttachmentsController@store - Validation error', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error('IIESAttachmentsController@store - Error', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            throw $e;
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
        $hasAnyFile = collect(self::IIES_FIELDS)
            ->contains(fn ($field) => $request->hasFile($field));

        if (! $hasAnyFile) {
            Log::info('IIESAttachmentsController@update - No files present; skipping mutation', [
                'project_id' => $projectId,
            ]);
            return response()->json([
                'message' => 'IIES attachments updated successfully.'
            ], 200);
        }

        Log::info('IIESAttachmentsController@update - Start', [
            'project_id' => $projectId
        ]);

        try {
            $result = ProjectAttachmentHandler::handle(
                $request,
                (string) $projectId,
                AttachmentContext::forIIES(),
                self::iiesFieldConfig()
            );

            if (!$result->success) {
                Log::warning('IIES attachment validation failed on update', [
                    'project_id' => $projectId,
                    'errors' => $result->errorsByField,
                ]);
                throw \Illuminate\Validation\ValidationException::withMessages($result->errorsByField);
            }

            LogHelper::logSafeRequest('Files received for update', $request, [
                'project_id' => $projectId,
            ]);

            return response()->json([
                'message' => 'IIES attachments updated successfully.',
                'attachments' => $result->attachmentRecord
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('IIESAttachmentsController@update - Validation error', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error('IIESAttachmentsController@update - Error', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            throw $e;
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

    /**
     * DOWNLOAD: download a specific attachment file
     */
    public function downloadFile($fileId)
    {
        try {
            Log::info('IIESAttachmentsController@downloadFile - Start', ['file_id' => $fileId]);

            $file = ProjectIIESAttachmentFile::findOrFail($fileId);

            Log::info('IIESAttachmentsController@downloadFile - File found', [
                'file_id' => $fileId,
                'file_path' => $file->file_path,
                'file_name' => $file->file_name
            ]);

            // Check if file exists in storage
            if (!Storage::disk('public')->exists($file->file_path)) {
                Log::error('IIESAttachmentsController@downloadFile - File not found on disk', [
                    'file_id' => $fileId,
                    'file_path' => $file->file_path
                ]);
                return response()->json(['error' => 'File not found'], 404);
            }

            // Log download for audit
            Log::info('IIESAttachmentsController@downloadFile - File downloaded', [
                'file_id' => $fileId,
                'file_name' => $file->file_name
            ]);

            return Storage::disk('public')->download($file->file_path, $file->file_name);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('IIESAttachmentsController@downloadFile - File record not found', ['file_id' => $fileId]);
            return response()->json(['error' => 'File record not found'], 404);
        } catch (\Exception $e) {
            Log::error('IIESAttachmentsController@downloadFile - Error', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to download file'], 500);
        }
    }

    /**
     * VIEW: view a specific attachment file (stream response)
     */
    public function viewFile($fileId)
    {
        try {
            Log::info('IIESAttachmentsController@viewFile - Start', ['file_id' => $fileId]);

            $file = ProjectIIESAttachmentFile::findOrFail($fileId);

            Log::info('IIESAttachmentsController@viewFile - File found', [
                'file_id' => $fileId,
                'file_path' => $file->file_path,
                'file_name' => $file->file_name
            ]);

            // Check if file exists in storage
            if (!Storage::disk('public')->exists($file->file_path)) {
                Log::error('IIESAttachmentsController@viewFile - File not found on disk', [
                    'file_id' => $fileId,
                    'file_path' => $file->file_path
                ]);
                return response()->json(['error' => 'File not found'], 404);
            }

            // Get file content and MIME type
            $fileContent = Storage::disk('public')->get($file->file_path);
            $mimeType = Storage::disk('public')->mimeType($file->file_path);

            // Log view for audit
            Log::info('IIESAttachmentsController@viewFile - File viewed', [
                'file_id' => $fileId,
                'file_name' => $file->file_name,
                'mime_type' => $mimeType
            ]);

            return response($fileContent, 200)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'inline; filename="' . $file->file_name . '"');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('IIESAttachmentsController@viewFile - File record not found', ['file_id' => $fileId]);
            return response()->json(['error' => 'File record not found'], 404);
        } catch (\Exception $e) {
            Log::error('IIESAttachmentsController@viewFile - Error', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to view file'], 500);
        }
    }

}
