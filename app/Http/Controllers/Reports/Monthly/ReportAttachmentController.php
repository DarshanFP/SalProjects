<?php

namespace App\Http\Controllers\Reports\Monthly;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reports\Monthly\ReportAttachment;
use App\Models\Reports\Monthly\DPReport;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Reports\Monthly\DPPhoto;

class ReportAttachmentController extends Controller
{
    // Configuration moved to config/attachments.php
    // Using config() helper for better maintainability

    public function store(Request $request, DPReport $report)
    {
        Log::info('=== ReportAttachmentController@store START ===');

        // Validate request data
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:2048', // 2MB max
            'file_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            Log::error('ReportAttachmentController@store - Validation failed', ['errors' => $validator->errors()]);
            return response()->json(['error' => 'Validation failed', 'details' => $validator->errors()], 422);
        }

        $file = $request->file('file');

        // Validate file type
        if (!$this->isValidFileType($file)) {
            Log::error('ReportAttachmentController@store - Invalid file type', [
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension()
            ]);
            return response()->json(['error' => 'Invalid file type. Only PDF, DOC, DOCX, XLS, and XLSX files are allowed.'], 400);
        }

        // Sanitize filename
        $filename = $this->sanitizeFilename($request->input('file_name'), $file->getClientOriginalExtension());

        // Get project
        $project = Project::where('project_id', $report->project_id)->first();
        if (!$project) {
            Log::error('ReportAttachmentController@store - Project not found', ['project_id' => $report->project_id]);
            return response()->json(['error' => 'Project not found'], 404);
        }

        // Create folder structure
        $monthYear = date('m_Y', strtotime($report->report_month_year));
        $folderPath = "REPORTS/{$project->project_id}/{$report->report_id}/attachments/{$monthYear}";

        // Ensure directory exists
        if (!Storage::disk('public')->exists($folderPath)) {
            Storage::disk('public')->makeDirectory($folderPath, 0755, true);
        }

        // Store file with transaction
        try {
            DB::beginTransaction();

            $path = $file->storeAs($folderPath, $filename, 'public');
            if (!$path) {
                throw new \Exception('File storage failed');
            }

            $publicUrl = Storage::url($path);

            $attachment = new ReportAttachment([
                'report_id' => $report->report_id,
                'file_name' => $filename,
                'file_path' => $path,
                'description' => $request->input('description', ''),
                'public_url' => $publicUrl,
            ]);

            if (!$attachment->save()) {
                throw new \Exception('Database insertion failed');
            }

            DB::commit();

            Log::info('ReportAttachmentController@store - Success', [
                'file_name' => $filename,
                'attachment_id' => $attachment->attachment_id
            ]);

            return response()->json([
                'success' => true,
                'attachment' => $attachment,
                'message' => 'File uploaded successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            // Clean up uploaded file if it exists
            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            Log::error('ReportAttachmentController@store - Error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to upload file: ' . $e->getMessage()], 500);
        }
    }

    public function downloadAttachment($id)
    {
        try {
            $attachment = ReportAttachment::findOrFail($id);

            // Check if file exists in storage
            if (!Storage::disk('public')->exists($attachment->file_path)) {
                Log::error('ReportAttachmentController@downloadAttachment - File not found', [
                    'attachment_id' => $id,
                    'file_path' => $attachment->file_path
                ]);
                return response()->json(['error' => 'File not found'], 404);
            }

            // Log download for audit
            Log::info('ReportAttachmentController@downloadAttachment - File downloaded', [
                'attachment_id' => $id,
                'file_name' => $attachment->file_name
            ]);

            return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('ReportAttachmentController@downloadAttachment - Attachment not found', ['attachment_id' => $id]);
            return response()->json(['error' => 'Attachment not found'], 404);
        } catch (\Exception $e) {
            Log::error('ReportAttachmentController@downloadAttachment - Error', [
                'attachment_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to download file'], 500);
        }
    }

    public function update(Request $request, $report_id)
    {
        Log::info('ReportAttachmentController@update - Data received', ['report_id' => $report_id]);

        $report = DPReport::where('report_id', $report_id)->firstOrFail();

        if (!$request->hasFile('file')) {
            return response()->json(['message' => 'No new file uploaded, existing files retained'], 200);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:2048',
            'file_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'details' => $validator->errors()], 422);
        }

        $file = $request->file('file');

        // Validate file type
        if (!$this->isValidFileType($file)) {
            return response()->json(['error' => 'Invalid file type. Only PDF, DOC, DOCX, XLS, and XLSX files are allowed.'], 400);
        }

        // Sanitize filename
        $filename = $this->sanitizeFilename($request->input('file_name'), $file->getClientOriginalExtension());

        // Get project
        $project = Project::where('project_id', $report->project_id)->first();
        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        // Create folder structure
        $monthYear = date('m_Y', strtotime($report->report_month_year));
        $folderPath = "REPORTS/{$project->project_id}/{$report->report_id}/attachments/{$monthYear}";

        // Ensure directory exists
        if (!Storage::disk('public')->exists($folderPath)) {
            Storage::disk('public')->makeDirectory($folderPath, 0755, true);
        }

        try {
            DB::beginTransaction();

            $path = $file->storeAs($folderPath, $filename, 'public');
            if (!$path) {
                throw new \Exception('File storage failed');
            }

            $publicUrl = Storage::url($path);

            $attachment = new ReportAttachment([
                'report_id' => $report->report_id,
                'file_name' => $filename,
                'file_path' => $path,
                'description' => $request->input('description', ''),
                'public_url' => $publicUrl,
            ]);

            if (!$attachment->save()) {
                throw new \Exception('Database insertion failed');
            }

            DB::commit();

            Log::info('ReportAttachmentController@update - Success', ['file_name' => $filename]);
            return response()->json([
                'success' => true,
                'attachment' => $attachment,
                'message' => 'New attachment added successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            Log::error('ReportAttachmentController@update - Error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to add attachment: ' . $e->getMessage()], 500);
        }
    }

    public function remove($id)
    {
        try {
            Log::info('Starting attachment removal', ['attachment_id' => $id]);

            $attachment = ReportAttachment::findOrFail($id);
            $filePath = $attachment->file_path;

            // Delete the file from storage first (faster operation)
            $fileDeleted = false;
            if (Storage::disk('public')->exists($filePath)) {
                $fileDeleted = Storage::disk('public')->delete($filePath);
                Log::info('Attachment file deletion attempt', [
                    'attachment_id' => $id,
                    'file_path' => $filePath,
                    'deleted' => $fileDeleted
                ]);
            } else {
                Log::warning('Attachment file not found in storage', [
                    'attachment_id' => $id,
                    'file_path' => $filePath
                ]);
            }

            // Delete the record from the database
            $attachment->delete();
            Log::info('Attachment record deleted from database', ['attachment_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Attachment removed successfully',
                'file_deleted' => $fileDeleted
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Attachment not found for removal', ['attachment_id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Attachment not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to remove attachment', [
                'attachment_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove the attachment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate file type
     */
    private function isValidFileType($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();

        // Get allowed types from config
        $allowedTypes = config('attachments.allowed_types.report_attachments');
        $allowedExtensions = $allowedTypes['extensions'] ?? [];
        $allowedMimeTypes = $allowedTypes['mime_types'] ?? [];

        return in_array($extension, $allowedExtensions) &&
               in_array($mimeType, $allowedMimeTypes);
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
     * Check if a file exists in storage
     */
    public function checkFileExists($id)
    {
        try {
            $attachment = ReportAttachment::findOrFail($id);
            $exists = Storage::disk('public')->exists($attachment->file_path);

            return response()->json([
                'exists' => $exists,
                'file_path' => $attachment->file_path,
                'file_name' => $attachment->file_name
            ]);
        } catch (\Exception $e) {
            Log::error('ReportAttachmentController@checkFileExists - Error checking file', [
                'attachment_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['exists' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Test method to verify file structure and paths
     */
    public function testFileStructure($report_id)
    {
        try {
            $report = DPReport::where('report_id', $report_id)->firstOrFail();
            $project = Project::where('project_id', $report->project_id)->firstOrFail();

            $monthYear = date('m_Y', strtotime($report->report_month_year));
            $attachmentsPath = "REPORTS/{$project->project_id}/{$report_id}/attachments/{$monthYear}";
            $photosPath = "REPORTS/{$project->project_id}/{$report_id}/photos/{$monthYear}";

            $attachments = ReportAttachment::where('report_id', $report_id)->get();
            $photos = DPPhoto::where('report_id', $report_id)->get();

            $result = [
                'report_id' => $report_id,
                'project_id' => $project->project_id,
                'month_year' => $monthYear,
                'attachments_path' => $attachmentsPath,
                'photos_path' => $photosPath,
                'attachments' => [],
                'photos' => [],
                'storage_exists' => [
                    'attachments_dir' => Storage::disk('public')->exists($attachmentsPath),
                    'photos_dir' => Storage::disk('public')->exists($photosPath)
                ]
            ];

            foreach ($attachments as $attachment) {
                $result['attachments'][] = [
                    'id' => $attachment->id,
                    'file_name' => $attachment->file_name,
                    'file_path' => $attachment->file_path,
                    'exists_in_storage' => Storage::disk('public')->exists($attachment->file_path),
                    'full_url' => asset('storage/' . $attachment->file_path)
                ];
            }

            foreach ($photos as $photo) {
                $result['photos'][] = [
                    'photo_id' => $photo->photo_id,
                    'photo_name' => $photo->photo_name,
                    'photo_path' => $photo->photo_path,
                    'exists_in_storage' => Storage::disk('public')->exists($photo->photo_path),
                    'full_url' => asset('storage/' . $photo->photo_path)
                ];
            }

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('ReportAttachmentController@testFileStructure - Error', [
                'report_id' => $report_id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Test method to create a sample attachment for testing
     */
    public function testCreateAttachment($report_id)
    {
        try {
            $report = DPReport::where('report_id', $report_id)->firstOrFail();
            $project = Project::where('project_id', $report->project_id)->firstOrFail();

            // Create a test file
            $testContent = "This is a test attachment file created at " . now();
            $filename = "test_attachment_" . date('Y-m-d_H-i-s') . ".txt";

            // Create folder structure: REPORTS/{project_id}/{report_id}/attachments/{month_year}/
            $monthYear = date('m_Y', strtotime($report->report_month_year));
            $folderPath = "REPORTS/{$project->project_id}/{$report->report_id}/attachments/{$monthYear}";

            // Store the test file
            $path = Storage::disk('public')->put($folderPath . '/' . $filename, $testContent);

            if (!$path) {
                return response()->json(['error' => 'Failed to create test file'], 500);
            }

            $fullPath = $folderPath . '/' . $filename;
            $publicUrl = Storage::url($fullPath);

            // Create database record
            $attachment = new ReportAttachment([
                'report_id' => $report->report_id,
                'file_name' => $filename,
                'file_path' => $fullPath,
                'description' => 'Test attachment created for system verification',
                'public_url' => $publicUrl,
            ]);

            if (!$attachment->save()) {
                return response()->json(['error' => 'Failed to save attachment record'], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Test attachment created successfully',
                'attachment' => [
                    'id' => $attachment->id,
                    'attachment_id' => $attachment->attachment_id,
                    'file_name' => $attachment->file_name,
                    'file_path' => $attachment->file_path,
                    'exists_in_storage' => Storage::disk('public')->exists($attachment->file_path),
                    'full_url' => $publicUrl
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('ReportAttachmentController@testCreateAttachment - Error', [
                'report_id' => $report_id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

