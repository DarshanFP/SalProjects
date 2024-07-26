<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\ProjectAttachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AttachmentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // Max size 10 MB
            'file_name' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        try {
            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $file = $request->file('file');
                $originalName = $request->input('file_name');
                $sanitizedName = str_replace(' ', '_', $originalName);
                $extension = $file->getClientOriginalExtension();
                $fileName = $sanitizedName . '.' . $extension;
                $path = $file->storeAs('public/attachments', $fileName);
                $publicUrl = Storage::url($path);

                $attachment = new ProjectAttachment([
                    'file_name' => $fileName,
                    'file_path' => $path,
                    'description' => $request->input('description'),
                    'public_url' => $publicUrl,
                    // Assuming 'project_id' is passed through hidden input if it's a specific project
                    'project_id' => $request->input('project_id')
                ]);

                $attachment->save();

                Log::info('Attachment saved', ['id' => $attachment->id, 'path' => $path]);
                return back()->with('success', 'Attachment uploaded successfully.');
            }
            return back()->with('error', 'Invalid file upload.');
        } catch (\Exception $e) {
            Log::error('Error uploading attachment: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while uploading the file.');
        }
    }
}
