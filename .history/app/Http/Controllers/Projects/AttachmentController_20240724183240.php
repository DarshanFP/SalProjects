<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectAttachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AttachmentController extends Controller
{
    public function store(Request $request, Project $project)
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
            return redirect()->route('projects.show', $project->id)->with('success', 'Attachments uploaded successfully.');
        } catch (\Exception $e) {
            Log::error('AttachmentController@store - Error', ['error' => $e->getMessage(), 'stack' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Failed to upload attachments. Please try again.');
        }
    }

    public function update(Request $request, Project $project)
    {
        Log::info('AttachmentController@update - Data received from form', $request->all());

        $validated = $request->validate([
            'attachments' => 'nullable|array',
            'attachments.*.file' => 'file|mimes:pdf,doc,docx,xlsx|max:15048',
            'attachments.*.description' => 'nullable|string',
            'file_name' => 'nullable|array',
            'file_name.*' => 'nullable|string',
        ]);

        try {
            // Optionally delete old files if replacing them
            ProjectAttachment::where('project_id', $project->project_id)->delete();

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

            Log::info('Attachments updated successfully', ['project_id' => $project->project_id]);
            return redirect()->route('projects.show', $project->id)->with('success', 'Attachments updated successfully.');
        } catch (\Exception $e) {
            Log::error('AttachmentController@update - Error', ['error' => $e->getMessage(), 'stack' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Failed to update attachments. Please try again.');
        }
    }
}
