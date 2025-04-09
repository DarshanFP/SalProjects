<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectAttachment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function store(Request $request, Project $project)
    {
        Log::info('AttachmentController@store - Starting file storage process', [
            'data' => $request->all(),
            'project_id' => $project->project_id
        ]);

        if (!$request->hasFile('file')) {
            Log::warning('AttachmentController@store - No file uploaded in request');
            return redirect()->back()->withErrors(['file' => 'No file uploaded']);
        }

        if (!$request->file('file')->isValid()) {
            Log::error('AttachmentController@store - Invalid file upload detected');
            return redirect()->back()->withErrors(['file' => 'Invalid file upload']);
        }

        Log::info('AttachmentController@store - Validating file input');
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // Match client-side: PDF, 10 MB
            'file_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        Log::info('AttachmentController@store - File validation passed');

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $filename = str_replace(' ', '_', $request->input('file_name')) . '.' . $extension;

        // Sanitize project type for folder name
        $projectType = str_replace([' ', '-', '/'], '_', $project->project_type);
        $storagePath = "project_attachments/{$projectType}/{$project->project_id}";

        Log::info('AttachmentController@store - Attempting to store file', [
            'filename' => $filename,
            'storage_path' => $storagePath
        ]);
        $path = $file->storeAs($storagePath, $filename, 'public');
        if (!$path) {
            Log::error('AttachmentController@store - File storage failed', [
                'filename' => $filename,
                'storage_path' => $storagePath,
                'project_id' => $project->project_id
            ]);
            return redirect()->back()->withErrors(['file' => 'File storage failed']);
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
            'description' => $request->input('description', ''),
            'public_url' => $publicUrl,
        ]);

        Log::info('AttachmentController@store - Saving attachment to database');
        if (!$attachment->save()) {
            Log::error('AttachmentController@store - Database insertion failed', [
                'project_id' => $project->project_id,
                'filename' => $filename
            ]);
            return redirect()->back()->withErrors(['file' => 'Database insertion failed']);
        }

        Log::info('AttachmentController@store - File uploaded and database updated successfully', [
            'file_name' => $filename,
            'path' => $path,
            'project_id' => $project->project_id,
            'attachment_id' => $attachment->id
        ]);
        return $attachment;
    }

    public function downloadAttachment($id)
    {
        Log::info('AttachmentController@downloadAttachment - Starting download process', [
            'attachment_id' => $id
        ]);

        try {
            Log::info('AttachmentController@downloadAttachment - Fetching attachment from database');
            $attachment = ProjectAttachment::findOrFail($id);
            $path = $attachment->file_path;

            Log::info('AttachmentController@downloadAttachment - Checking file existence', [
                'path' => $path
            ]);
            if (!Storage::disk('public')->exists($path)) {
                Log::error('AttachmentController@downloadAttachment - File not found on disk', [
                    'path' => $path,
                    'attachment_id' => $id
                ]);
                abort(404, 'File not found.');
            }

            Log::info('AttachmentController@downloadAttachment - File found, initiating download', [
                'file_name' => $attachment->file_name,
                'path' => $path
            ]);
            return Storage::disk('public')->download($path, $attachment->file_name);
        } catch (\Exception $e) {
            Log::error('AttachmentController@downloadAttachment - Error during download', [
                'attachment_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors('Failed to download the file.');
        }
    }

    public function update(Request $request, $project_id)
{
    Log::info('AttachmentController@update - Starting update process', [
        'data' => $request->all(),
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

    Log::info('AttachmentController@update - Validating file input');
    $request->validate([
        'file' => 'required|file|mimes:pdf|max:10240',
        'file_name' => 'required|string|max:255',
        'description' => 'nullable|string',
    ]);
    Log::info('AttachmentController@update - File validation passed');

    // Delete existing attachment (if any)
    $existingAttachment = $project->attachments->first();
    if ($existingAttachment) {
        Log::info('AttachmentController@update - Deleting existing attachment', [
            'attachment_id' => $existingAttachment->id,
            'file_path' => $existingAttachment->file_path
        ]);
        Storage::disk('public')->delete($existingAttachment->file_path);
        $existingAttachment->delete();
        Log::info('AttachmentController@update - Existing attachment deleted');
    }

    // Store new file
    $file = $request->file('file');
    $extension = $file->getClientOriginalExtension();
    $filename = str_replace(' ', '_', $request->input('file_name')) . '.' . $extension;
    $projectType = str_replace([' ', '-', '/'], '_', $project->project_type);
    $storagePath = "project_attachments/{$projectType}/{$project->project_id}";

    Log::info('AttachmentController@update - Attempting to store new file', [
        'filename' => $filename,
        'storage_path' => $storagePath
    ]);
    $path = $file->storeAs($storagePath, $filename, 'public');
    if (!$path) {
        Log::error('AttachmentController@update - File storage failed', [
            'filename' => $filename,
            'storage_path' => $storagePath
        ]);
        return redirect()->back()->withErrors(['file' => 'File storage failed']);
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
        'description' => $request->input('description', ''),
        'public_url' => $publicUrl,
    ]);

    Log::info('AttachmentController@update - Saving new attachment to database');
    if (!$attachment->save()) {
        Log::error('AttachmentController@update - Database insertion failed', [
            'project_id' => $project->project_id,
            'filename' => $filename
        ]);
        return redirect()->back()->withErrors(['file' => 'Database insertion failed']);
    }

    Log::info('AttachmentController@update - New attachment replaced successfully', [
        'file_name' => $filename,
        'path' => $path,
        'project_id' => $project->project_id,
        'attachment_id' => $attachment->id
    ]);
    return redirect()->back()->with('success', 'Attachment replaced successfully');
}
}
