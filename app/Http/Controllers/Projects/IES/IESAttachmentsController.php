<?php

namespace App\Http\Controllers\Projects\IES;

use App\Constants\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Helpers\LogHelper;
use App\Helpers\ProjectPermissionHelper;
use App\Models\OldProjects\IES\ProjectIESAttachmentFile;
use App\Services\Attachment\AttachmentContext;
use App\Services\ProjectAttachmentHandler;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class IESAttachmentsController extends Controller
{
    private const IES_FIELDS = [
        'aadhar_card', 'fee_quotation', 'scholarship_proof', 'medical_confirmation',
        'caste_certificate', 'self_declaration', 'death_certificate', 'request_letter',
    ];

    /** @return array<string, array> */
    private static function iesFieldConfig(): array
    {
        return array_fill_keys(self::IES_FIELDS, []);
    }

    // 🟢 STORE ATTACHMENTS
    public function store(FormRequest $request, $projectId)
    {
        $hasAnyFile = collect(self::IES_FIELDS)->contains(fn ($field) => $request->hasFile($field));
        if (! $hasAnyFile) {
            Log::info('IESAttachmentsController@store - No files present; skipping mutation', [
                'project_id' => $projectId,
            ]);
            return response()->json(['message' => 'IES attachments saved successfully.'], 200);
        }

        DB::beginTransaction();

        try {
            Log::info('Storing IES attachments', ['project_id' => $projectId]);

            $result = ProjectAttachmentHandler::handle(
                $request,
                (string) $projectId,
                AttachmentContext::forIES(),
                self::iesFieldConfig()
            );

            if (!$result->success) {
                DB::rollBack();
                Log::warning('IES attachment validation failed', [
                    'project_id' => $projectId,
                    'errors' => $result->errorsByField,
                ]);
                return response()->json([
                    'error' => 'Failed to save attachments.',
                    'errors' => $result->errorsByField,
                ], 422);
            }

            DB::commit();
            return response()->json(['message' => 'IES attachments saved successfully.'], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error saving IES attachments', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save attachments.'], 500);
        }
    }

    // 🟠 SHOW ATTACHMENTS
    public function show($projectId)
    {
        try {
            Log::info('Fetching IES attachments', ['project_id' => $projectId]);

            $attachments = \App\Models\OldProjects\IES\ProjectIESAttachments::where('project_id', $projectId)->first();

            if (!$attachments) {
                return null;
            }

            return $attachments;
        } catch (\Exception $e) {
            Log::error('Error fetching IES attachments', ['error' => $e->getMessage()]);
            return null;
        }
    }


    // 🟡 EDIT ATTACHMENTS
    public function edit($projectId)
    {
        try {
            $attachments = \App\Models\OldProjects\IES\ProjectIESAttachments::where('project_id', $projectId)->firstOrFail();

            Log::info('Fetched IES attachments for editing', [
                'project_id' => $projectId,
                'attachments' => $attachments
            ]);

            return $attachments;
        } catch (\Exception $e) {
            Log::error('Error fetching IES attachments for editing', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function update(FormRequest $request, $projectId)
    {
        $hasAnyFile = collect(self::IES_FIELDS)->contains(fn ($field) => $request->hasFile($field));
        if (! $hasAnyFile) {
            Log::info('IESAttachmentsController@update - No files present; skipping mutation', [
                'project_id' => $projectId,
            ]);
            return response()->json(['message' => 'IES Attachments updated successfully.'], 200);
        }

        DB::beginTransaction();

        try {
            Log::info('Starting update process for IES Attachments', ['project_id' => $projectId]);

            $result = ProjectAttachmentHandler::handle(
                $request,
                (string) $projectId,
                AttachmentContext::forIES(),
                self::iesFieldConfig()
            );

            if (!$result->success) {
                DB::rollBack();
                Log::warning('IES attachment validation failed on update', [
                    'project_id' => $projectId,
                    'errors' => $result->errorsByField,
                ]);
                return response()->json([
                    'error' => 'Failed to update IES Attachments.',
                    'errors' => $result->errorsByField,
                ], 422);
            }

            DB::commit();

            Log::info('IES Attachments updated successfully', ['project_id' => $projectId]);
            LogHelper::logSafeRequest('Files received for update', $request, [
                'project_id' => $projectId,
            ]);

            return response()->json(['message' => 'IES Attachments updated successfully.'], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error updating IES Attachments', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to update IES Attachments.'], 500);
        }
    }



    // 🔵 DESTROY ATTACHMENTS
    public function destroy($projectId)
    {
        DB::beginTransaction();

        try {
            $attachments = \App\Models\OldProjects\IES\ProjectIESAttachments::where('project_id', $projectId)->firstOrFail();
            \Storage::deleteDirectory("project_attachments/IES/{$projectId}");
            $attachments->delete();

            DB::commit();
            return response()->json(['message' => 'IES attachments deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete attachments.'], 500);
        }
    }

    /**
     * PER-FILE DELETE: remove one IES attachment file.
     * Mutation: enforces province, editable status, canEdit. Model deleting event removes storage.
     * TEMPORARY: Debug logging added; remove after issue fixed.
     */
    public function destroyFile($fileId)
    {
        try {
            Log::info('IES Delete Attempt', [
                'file_id' => $fileId,
                'user_id' => Auth::id(),
            ]);

            $file = ProjectIESAttachmentFile::findOrFail($fileId);

            $project = $file->project ?? $file->iesAttachment?->project;
            if (! $project) {
                return response()->json(['error' => 'File record not found'], 404);
            }

            $user = Auth::user();
            if (! ProjectPermissionHelper::passesProvinceCheck($project, $user)) {
                Log::warning('IES Delete Blocked - province_mismatch', [
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
                Log::warning('IES Delete Blocked - not_editable', [
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
                Log::warning('IES Delete Blocked - cannot_edit', [
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

            Log::info('IES Delete Success', [
                'file_id' => $fileId,
                'project_id' => $project->project_id ?? $project->id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Attachment deleted successfully.',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'File record not found'], 404);
        } catch (\Exception $e) {
            Log::error('IES Delete Exception', [
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

    /**
     * DOWNLOAD: download a specific IES attachment file
     * Streams file through controller - works without storage symlink on production.
     * Guard: province isolation, canView — read allowed even when project is approved (403 on failure).
     */
    public function downloadFile($fileId)
    {
        try {
            Log::info('IESAttachmentsController@downloadFile - Start', ['file_id' => $fileId]);

            $file = ProjectIESAttachmentFile::findOrFail($fileId);

            $project = $file->project ?? $file->iesAttachment?->project;
            if (! $project) {
                Log::warning('IESAttachmentsController@downloadFile - No project for file', ['file_id' => $fileId]);
                return response()->json(['error' => 'File record not found'], 404);
            }

            $user = Auth::user();
            if (! ProjectPermissionHelper::passesProvinceCheck($project, $user)) {
                abort(403);
            }
            if (! ProjectPermissionHelper::canView($project, $user)) {
                abort(403);
            }

            Log::info('IESAttachmentsController@downloadFile - File found', [
                'file_id' => $fileId,
                'file_path' => $file->file_path,
                'file_name' => $file->file_name,
            ]);

            if (!Storage::disk('public')->exists($file->file_path)) {
                Log::error('IESAttachmentsController@downloadFile - File not found on disk', [
                    'file_id' => $fileId,
                    'file_path' => $file->file_path,
                ]);
                return response()->json(['error' => 'File not found'], 404);
            }

            Log::info('IESAttachmentsController@downloadFile - File downloaded', [
                'file_id' => $fileId,
                'file_name' => $file->file_name,
            ]);

            return Storage::disk('public')->download($file->file_path, $file->file_name);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('IESAttachmentsController@downloadFile - File record not found', ['file_id' => $fileId]);
            return response()->json(['error' => 'File record not found'], 404);
        } catch (\Exception $e) {
            Log::error('IESAttachmentsController@downloadFile - Error', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to download file'], 500);
        }
    }

    /**
     * VIEW: view a specific IES attachment file (stream response)
     * Streams file through controller - works without storage symlink on production.
     * Guard: province isolation, canView — read allowed even when project is approved (403 on failure).
     */
    public function viewFile($fileId)
    {
        try {
            Log::info('IESAttachmentsController@viewFile - Start', ['file_id' => $fileId]);

            $file = ProjectIESAttachmentFile::findOrFail($fileId);

            $project = $file->project ?? $file->iesAttachment?->project;
            if (! $project) {
                Log::warning('IESAttachmentsController@viewFile - No project for file', ['file_id' => $fileId]);
                return response()->json(['error' => 'File record not found'], 404);
            }

            $user = Auth::user();
            if (! ProjectPermissionHelper::passesProvinceCheck($project, $user)) {
                abort(403);
            }
            if (! ProjectPermissionHelper::canView($project, $user)) {
                abort(403);
            }

            Log::info('IESAttachmentsController@viewFile - File found', [
                'file_id' => $fileId,
                'file_path' => $file->file_path,
                'file_name' => $file->file_name,
            ]);

            if (!Storage::disk('public')->exists($file->file_path)) {
                Log::error('IESAttachmentsController@viewFile - File not found on disk', [
                    'file_id' => $fileId,
                    'file_path' => $file->file_path,
                ]);
                return response()->json(['error' => 'File not found'], 404);
            }

            $fileContent = Storage::disk('public')->get($file->file_path);
            $mimeType = Storage::disk('public')->mimeType($file->file_path);

            Log::info('IESAttachmentsController@viewFile - File viewed', [
                'file_id' => $fileId,
                'file_name' => $file->file_name,
                'mime_type' => $mimeType,
            ]);

            return response($fileContent, 200)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'inline; filename="' . $file->file_name . '"');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('IESAttachmentsController@viewFile - File record not found', ['file_id' => $fileId]);
            return response()->json(['error' => 'File record not found'], 404);
        } catch (\Exception $e) {
            Log::error('IESAttachmentsController@viewFile - Error', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to view file'], 500);
        }
    }
}
