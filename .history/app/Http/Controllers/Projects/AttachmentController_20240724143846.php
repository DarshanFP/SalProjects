<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\ProjectAttachment;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    /**
     * Store attachments for a project.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $projectId
     * @return void
     */
    public function store(Request $request, $projectId)
    {
        $request->validate([
            'attachments.*.file' => 'required|file|mimes:pdf,doc,docx,xlsx',
            'file_name.*' => 'required|string|max:255',
            'attachments.*.description' => 'nullable|string'
        ]);

        foreach ($request->file('attachments') as $index => $file) {
            $path = $file['file']->store('attachments', 'public');

            ProjectAttachment::create([
                'project_id' => $projectId,
                'file_name' => $request->input("file_name.{$index}"),
                'file_path' => $path,
                'description' => $request->input("attachments.{$index}.description"),
                'public_url' => Storage::disk('public')->url($path)
            ]);
        }
    }
}
