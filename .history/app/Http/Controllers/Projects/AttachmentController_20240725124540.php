<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\ProjectAttachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AttachmentController extends Controller
{
    public function store(Request $request, $project)
    {
        Log::info('AttachmentController@store - Data received', ['data' => $request->all()]);

        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $file = $request->file('file');
            $originalFileName = $request->file_name; // Assuming 'file_name' is sent via request
            $originalFileName = str_replace(' ', '_', $originalFileName); // Replace spaces with underscores
            Log::info('AttachmentController@store - File name prepared', ['file_name' => $originalFileName]);

            // Validate the file size
            if ($file->getSize() <= 10000000) { // 10 MB in bytes
                $extension = $file->getClientOriginalExtension();
                if ($extension === 'pdf') { // Ensure it is a PDF
                    $newFileName = $originalFileName . '_' . time() . '.' . $extension;
                    $path = $file->storeAs('public/attachments', $newFileName);
                    $publicUrl = Storage::url($path);

                    Log::info('AttachmentController@store - File stored', ['path' => $path, 'public_url' => $publicUrl]);

                    try {
                        $attachment = new ProjectAttachment([
                            'project_id' => $project->project_id,
                            'file_name' => $newFileName,
                            'file_path' => $path,
                            'description' => $request->description,
                            'public_url' => $publicUrl,
                        ]);
                        $attachment->save();

                        Log::info('AttachmentController@store - Database updated successfully', ['attachment_id' => $attachment->id]);
                    } catch (\Exception $e) {
                        Log::error('AttachmentController@store - Database insertion failed', ['error' => $e->getMessage(), 'stack' => $e->getTraceAsString()]);
                        return back()->withErrors(['msg' => 'Failed to save attachment details in database.']);
                    }
                } else {
                    Log::error('Uploaded file is not a PDF');
                    return back()->withErrors(['msg' => 'Only PDF files are allowed.']);
                }
            } else {
                Log::error('Uploaded file exceeds the maximum size limit of 10 MB');
                return back()->withErrors(['msg' => 'File size must not exceed 10 MB.']);
            }
        } else {
            Log::error('No file uploaded or file is invalid');
            return back()->withErrors(['msg' => 'No file uploaded or file is invalid.']);
        }

        return back()->with('success', 'Attachment processed successfully.');
    }

    public function update(Request $request, $id, $project)
    {
        // This function would be similar to store, handling the update logic.
    }
}
