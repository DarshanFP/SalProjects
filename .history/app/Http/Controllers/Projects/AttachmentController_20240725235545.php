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
        Log::info('AttachmentController@store - Data received', ['data' => $request->all()]);

        if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
            Log::error('AttachmentController@store - Invalid file upload');
            return response()->json(['error' => 'Invalid file upload'], 400);
        }

        $file = $request->file('file');
        $filename = $request->input('file_name', 'default_filename') . '.' . $file->getClientOriginalExtension();
        $filename = str_replace(' ', '_', $filename); // Replace spaces with underscores

        $path = $file->storeAs('public/attachments', $filename);
        if (!$path) {
            Log::error('AttachmentController@store - File storage failed');
            return response()->json(['error' => 'File storage failed'], 500);
        }

        $publicUrl = Storage::url($path);

        $attachment = new ProjectAttachment([
            'project_id' => $project->project_id,
            'file_name' => $filename,
            'file_path' => $path,
            'description' => $request->input('description', ''),
            'public_url' => $publicUrl,
        ]);

        if (!$attachment->save()) {
            Log::error('AttachmentController@store - Database insertion failed');
            return response()->json(['error' => 'Database insertion failed'], 500);
        }

        Log::info('AttachmentController@store - File uploaded and database updated', ['file_name' => $filename, 'project_id' => $project->project_id]);
        return response()->json(['message' => 'File uploaded successfully', 'path' => $publicUrl], 200);
    }

    
}
