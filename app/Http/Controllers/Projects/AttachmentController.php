<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectAttachment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AttachmentController extends Controller
{
    // Allowed file types and their MIME types
    private const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx'];
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    private const MAX_FILE_SIZE = 2097152; // 2MB in bytes

    public function store(Request $request, Project $project)
    {
        Log::info('AttachmentController@store - Starting file storage process', [
            'project_id' => $project->project_id
        ]);

        // Validate request data
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:2048', // 2MB max
            'file_name' => 'required|string|max:255',
            'attachment_description' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            Log::error('AttachmentController@store - Validation failed', ['errors' => $validator->errors()]);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if (!$request->hasFile('file')) {
            Log::warning('AttachmentController@store - No file uploaded in request');
            return redirect()->back()->withErrors(['file' => 'No file uploaded']);
        }

        if (!$request->file('file')->isValid()) {
            Log::error('AttachmentController@store - Invalid file upload detected');
            return redirect()->back()->withErrors(['file' => 'Invalid file upload']);
        }

        $file = $request->file('file');

        // Validate file type
        if (!$this->isValidFileType($file)) {
            Log::error('AttachmentController@store - Invalid file type', [
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension()
            ]);
            return redirect()->back()->withErrors(['file' => 'Only PDF, DOC, and DOCX files are allowed'])->withInput();
        }

        // Sanitize filename
        $filename = $this->sanitizeFilename($request->input('file_name'), $file->getClientOriginalExtension());

        // Sanitize project type for folder name
        $projectType = $this->sanitizeProjectType($project->project_type);
        $storagePath = "project_attachments/{$projectType}/{$project->project_id}";

        // Ensure directory exists
        if (!Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->makeDirectory($storagePath, 0755, true);
        }

        try {
            DB::beginTransaction();

            Log::info('AttachmentController@store - Attempting to store file', [
                'filename' => $filename,
                'storage_path' => $storagePath
            ]);

            $path = $file->storeAs($storagePath, $filename, 'public');
            if (!$path) {
                throw new \Exception('File storage failed');
            }

            $publicUrl = Storage::url($path);
            Log::info('AttachmentController@store - File stored successfully', [
                'path' => $path,
                'public_url' => $publicUrl
            ]);

            $attachment = new ProjectAttachment([
                'project_id' => $project->project_id,
                'file_name' => $filename,
                'file_path' => $path,
                'description' => $request->input('attachment_description', ''),
                'public_url' => $publicUrl,
            ]);

            Log::info('AttachmentController@store - Saving attachment to database');
            if (!$attachment->save()) {
                throw new \Exception('Database insertion failed');
            }

            DB::commit();

            Log::info('AttachmentController@store - File uploaded and database updated successfully', [
                'file_name' => $filename,
                'path' => $path,
                'project_id' => $project->project_id,
                'attachment_id' => $attachment->id
            ]);

            return redirect()->back()->with('success', 'Attachment uploaded successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            // Clean up uploaded file if it exists
            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            Log::error('AttachmentController@store - Error', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors(['file' => 'Failed to upload file: ' . $e->getMessage()])->withInput();
        }
    }

    public function downloadAttachment($id)
    {
        Log::info('AttachmentController@downloadAttachment - Starting download process', [
            'attachment_id' => $id
        ]);

        try {
            Log::info('AttachmentController@downloadAttachment - Fetching attachment from database');
            $attachment = ProjectAttachment::findOrFail($id);

            Log::info('AttachmentController@downloadAttachment - Checking file existence', [
                'path' => $attachment->file_path
            ]);

            if (!Storage::disk('public')->exists($attachment->file_path)) {
                Log::error('AttachmentController@downloadAttachment - File not found on disk', [
                    'path' => $attachment->file_path,
                    'attachment_id' => $id
                ]);
                return redirect()->back()->withErrors(['file' => 'File not found']);
            }

            // Log download for audit
            Log::info('AttachmentController@downloadAttachment - File downloaded', [
                'file_name' => $attachment->file_name,
                'path' => $attachment->file_path
            ]);

            return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('AttachmentController@downloadAttachment - Attachment not found', ['attachment_id' => $id]);
            return redirect()->back()->withErrors(['file' => 'Attachment not found']);
        } catch (\Exception $e) {
            Log::error('AttachmentController@downloadAttachment - Error during download', [
                'attachment_id' => $id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->withErrors(['file' => 'Failed to download the file']);
        }
    }

    public function update(Request $request, $project_id)
    {
        Log::info('AttachmentController@update - Starting update process', [
            'project_id' => $project_id
        ]);

        Log::info('AttachmentController@update - Fetching project from database');
        $project = Project::where('project_id', $project_id)->firstOrFail();

        if (!$request->hasFile('file')) {
            Log::info('AttachmentController@update - No new file uploaded, retaining existing attachment');
            return redirect()->back()->with('message', 'No new file uploaded, existing attachment retained');
        }

        if (!$request->file('file')->isValid()) {
            Log::error('AttachmentController@update - Invalid file upload detected');
            return redirect()->back()->withErrors(['file' => 'Invalid file upload']);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:2048', // 2MB max
            'file_name' => 'required|string|max:255',
            'attachment_description' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            Log::error('AttachmentController@update - Validation failed', ['errors' => $validator->errors()]);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $file = $request->file('file');

        // Validate file type
        if (!$this->isValidFileType($file)) {
            return redirect()->back()->withErrors(['file' => 'Only PDF, DOC, and DOCX files are allowed'])->withInput();
        }

        // Sanitize filename
        $filename = $this->sanitizeFilename($request->input('file_name'), $file->getClientOriginalExtension());

        // Sanitize project type for folder name
        $projectType = $this->sanitizeProjectType($project->project_type);
        $storagePath = "project_attachments/{$projectType}/{$project->project_id}";

        // Ensure directory exists
        if (!Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->makeDirectory($storagePath, 0755, true);
        }

        try {
            DB::beginTransaction();

            // Delete existing attachment (if any)
            $existingAttachment = $project->attachments->first();
            if ($existingAttachment) {
                Log::info('AttachmentController@update - Deleting existing attachment', [
                    'attachment_id' => $existingAttachment->id,
                    'file_path' => $existingAttachment->file_path
                ]);

                if (Storage::disk('public')->exists($existingAttachment->file_path)) {
                    Storage::disk('public')->delete($existingAttachment->file_path);
                }
                $existingAttachment->delete();
                Log::info('AttachmentController@update - Existing attachment deleted');
            }

            // Store new file
            Log::info('AttachmentController@update - Attempting to store new file', [
                'filename' => $filename,
                'storage_path' => $storagePath
            ]);

            $path = $file->storeAs($storagePath, $filename, 'public');
            if (!$path) {
                throw new \Exception('File storage failed');
            }

            $publicUrl = Storage::url($path);
            Log::info('AttachmentController@update - New file stored successfully', [
                'path' => $path,
                'public_url' => $publicUrl
            ]);

            $attachment = new ProjectAttachment([
                'project_id' => $project->project_id,
                'file_name' => $filename,
                'file_path' => $path,
                'description' => $request->input('attachment_description', ''),
                'public_url' => $publicUrl,
            ]);

            Log::info('AttachmentController@update - Saving new attachment to database');
            if (!$attachment->save()) {
                throw new \Exception('Database insertion failed');
            }

            DB::commit();

            Log::info('AttachmentController@update - New attachment replaced successfully', [
                'file_name' => $filename,
                'path' => $path,
                'project_id' => $project->project_id,
                'attachment_id' => $attachment->id
            ]);

            return redirect()->back()->with('success', 'Attachment replaced successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            // Clean up uploaded file if it exists
            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            Log::error('AttachmentController@update - Error', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors(['file' => 'Failed to replace attachment: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Validate file type
     */
    private function isValidFileType($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();

        return in_array($extension, self::ALLOWED_EXTENSIONS) &&
               in_array($mimeType, self::ALLOWED_MIME_TYPES);
    }

    /**
     * Sanitize filename to prevent path traversal
     */
    private function sanitizeFilename($filename, $extension)
    {
        // Remove any path separators and dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '_', $filename);
        $filename = trim($filename, '._');

        // Ensure filename is not empty
        if (empty($filename)) {
            $filename = 'attachment';
        }

        return $filename . '.' . $extension;
    }

    /**
     * Sanitize project type for folder name
     */
    private function sanitizeProjectType($projectType)
    {
        // Remove or replace dangerous characters
        $sanitized = preg_replace('/[^a-zA-Z0-9\-\_]/', '_', $projectType);
        $sanitized = trim($sanitized, '._');

        // Ensure it's not empty
        if (empty($sanitized)) {
            $sanitized = 'unknown_type';
        }

        return $sanitized;
    }
}
