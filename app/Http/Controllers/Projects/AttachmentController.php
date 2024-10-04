<!-- app/Http/Controllers/Projects/AttachmentController.php -->
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
        $filename = str_replace(' ', '_', $filename);

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
        return $attachment;
    }

    public function downloadAttachment($id)
    {
        try {
            $attachment = ProjectAttachment::findOrFail($id);
            $path = $attachment->file_path;

            if (!Storage::exists($path)) {
                abort(404, 'File not found.');
            }

            return Storage::download($path, $attachment->file_name);
        } catch (\Exception $e) {
            Log::error('Failed to download attachment', ['error' => $e->getMessage()]);
            return back()->withErrors('Failed to download the file.');
        }
    }

    public function update(Request $request, $project_id)
{
    Log::info('AttachmentController@update - Data received', ['data' => $request->all(), 'project_id' => $project_id]);

    $project = Project::where('project_id', $project_id)->firstOrFail();

    if (!$request->hasFile('file')) {
        Log::info('AttachmentController@update - No new file uploaded');
        return response()->json(['message' => 'No new file uploaded, existing files retained'], 200);
    }

    if (!$request->file('file')->isValid()) {
        Log::error('AttachmentController@update - Invalid file upload');
        return response()->json(['error' => 'Invalid file upload'], 400);
    }


    $file = $request->file('file');
    $filename = $request->input('file_name', 'default_filename') . '.' . $file->getClientOriginalExtension();
    $filename = str_replace(' ', '_', $filename);

    $path = $file->storeAs('public/attachments', $filename);
    if (!$path) {
        Log::error('AttachmentController@update - File storage failed');
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
        Log::error('AttachmentController@update - Database insertion failed');
        return response()->json(['error' => 'Database insertion failed'], 500);
    }

    Log::info('AttachmentController@update - New attachment added and database updated', ['file_name' => $filename, 'project_id' => $project->project_id]);
    return $attachment;
}

    // public function update(Request $request, $project_id)
    // {
    // {
    //     Log::info('AttachmentController@update - Data received', ['data' => $request->all(), 'project_id' => $project_id]);

    //     $project = Project::findOrFail($project_id);

    //     if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
    //         Log::error('AttachmentController@update - Invalid file upload');
    //         return response()->json(['error' => 'Invalid file upload'], 400);
    //     }

    //     $file = $request->file('file');
    //     $filename = $request->input('file_name', 'default_filename') . '.' . $file->getClientOriginalExtension();
    //     $filename = str_replace(' ', '_', $filename);

    //     $path = $file->storeAs('public/attachments', $filename);
    //     if (!$path) {
    //         Log::error('AttachmentController@update - File storage failed');
    //         return response()->json(['error' => 'File storage failed'], 500);
    //     }

    //     $publicUrl = Storage::url($path);

    //     $attachment = new ProjectAttachment([
    //         'project_id' => $project->project_id,
    //         'file_name' => $filename,
    //         'file_path' => $path,
    //         'description' => $request->input('description', ''),
    //         'public_url' => $publicUrl,
    //     ]);

    //     if (!$attachment->save()) {
    //         Log::error('AttachmentController@update - Database insertion failed');
    //         return response()->json(['error' => 'Database insertion failed'], 500);
    //     }

    //     Log::info('AttachmentController@update - New attachment added and database updated', ['file_name' => $filename, 'project_id' => $project->project_id]);
    //     return $attachment;
    // }
}
