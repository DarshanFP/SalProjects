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
        Log::info('AttachmentController@store - Data received from form', $request->all());

        $validated = $request->validate([
            'attachments' => 'nullable|array',
            'attachments.*.file' => 'required|file|mimes:pdf,doc,docx,xlsx|max:15048',
            'file_name' => 'nullable|array',
            'attachments.*.description' => 'nullable|string',
        ]);

        try {
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $index => $attachment) {
                    $originalFileName = $request->file_name[$index] ?? 'attachment';
                    $originalFileName = str_replace(' ', '_', $originalFileName); // Replace spaces with underscores
                    $extension = $attachment->getClientOriginalExtension();
                    $newFileName = pathinfo($originalFileName, PATHINFO_FILENAME) . '_' . time() . '.' . $extension;
                    $path = $attachment->storeAs('public/attachments', $newFileName); // Ensure correct storage path
                    $publicUrl = Storage::url($path); // Get the public URL

                    ProjectAttachment::create([
                        'project_id' => $project->project_id,
                        'file_path' => 'attachments/' . $newFileName, // Save relative path
                        'file_name' => $originalFileName,
                        'description' => $request->attachments[$index]['description'] ?? '',
                        'public_url' => $publicUrl, // Save the public URL
                    ]);
                }
            }

            Log::info('AttachmentController@store - Data passed to database', $project->attachments->toArray());

            return $project;
        } catch (\Exception $e) {
            Log::error('AttachmentController@store - Error', ['error' => $e->getMessage(), 'stack' => $e->getTraceAsString()]);
            throw $e;
        }
    }

    public function update(Request $request, $project)
    {
        Log::info('AttachmentController@update - Data received from form', $request->all());

        $validated = $request->validate([
            'attachments' => 'nullable|array',
            'attachments.*.file' => 'required|file|mimes:pdf,doc,docx,xlsx|max:15048',
            'file_name' => 'nullable|array',
            'attachments.*.description' => 'nullable|string',
        ]);

        try {
            // Delete old attachments if any
            ProjectAttachment::where('project_id', $project->project_id)->delete();

             // Insert attachments
             if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $index => $attachment) {
                    $originalFileName = $request->file_name[$index];
                    $originalFileName = str_replace(' ', '_', $originalFileName); // Replace spaces with underscores
                    $extension = $attachment->getClientOriginalExtension();
                    $newFileName = pathinfo($originalFileName, PATHINFO_FILENAME) . '_' . time() . '.' . $extension;
                    $path = $attachment->storeAs('public/attachments', $newFileName); // Ensure correct storage path
                    $publicUrl = Storage::url($path); // Get the public URL

                    ProjectAttachment::create([
                        'project_id' => $project->project_id,
                        'file_path' => 'attachments/' . $newFileName, // Save relative path
                        'file_name' => $originalFileName,
                        'description' => $request->attachments[$index]['description'] ?? '',
                        'public_url' => $publicUrl, // Save the public URL
                    ]);
                }
            }

            Log::info('Project updated successfully', ['project_id' => $project->project_id]);

            return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating project', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'There was an error updating the project. Please try again.');
        }
    }
}
