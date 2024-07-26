<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\ProjectAttachment;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AttachmentController extends Controller
{
    public function store(Request $request, $project_id)
    {
        Log::info('AttachmentController@store - Data received from form', $request->all());

        $validated = $request->validate([
            'attachments' => 'nullable|array',
            'attachments.*.file' => 'required|file|mimes:pdf,doc,docx,xlsx|max:15048',
            'attachments.*.description' => 'nullable|string',
            'file_name' => 'nullable|array',
            'file_name.*' => 'nullable|string',
        ]);

        try {
            $project = Project::findOrFail($project_id);
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $index => $file) {
                    $file_name = $request->file_name[$index] ?? $file->getClientOriginalName();
                    $file_path = $file->storeAs('public/attachments', $file_name);
                    $public_url = Storage::url($file_path);

                    ProjectAttachment::create([
                        'project_id' => $project->project_id,
                        'file_path' => $file_path,
                        'file_name' => $file_name,
                        'description' => $request->input("attachments.$index.description", ''),
                        'public_url' => $public_url,
                    ]);
                }
            }

            Log::info('Attachments stored successfully', ['project_id' => $project->project_id]);
            return redirect()->route('projects.show', $project->$project_id)->with('success', 'Attachments uploaded successfully.');
        } catch (\Exception $e) {
            Log::error('AttachmentController@store - Error', ['error' => $e->getMessage(), 'stack' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Failed to upload attachments. Please try again.');
        }
    }
}
