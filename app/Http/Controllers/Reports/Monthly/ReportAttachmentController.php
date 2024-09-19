<?php

namespace App\Http\Controllers\Reports\Monthly;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reports\Monthly\ReportAttachment;
use App\Models\Reports\Monthly\DPReport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReportAttachmentController extends Controller
{
    public function store(Request $request, DPReport $report)
    {
        Log::info('ReportAttachmentController@store - Data received', ['data' => $request->all()]);

        if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
            Log::error('ReportAttachmentController@store - Invalid file upload');
            return response()->json(['error' => 'Invalid file upload'], 400);
        }

        $file = $request->file('file');
        $filename = $request->input('file_name', 'default_filename') . '.' . $file->getClientOriginalExtension();
        $filename = str_replace(' ', '_', $filename);

        $path = $file->storeAs('public/report_attachments', $filename);
        if (!$path) {
            Log::error('ReportAttachmentController@store - File storage failed');
            return response()->json(['error' => 'File storage failed'], 500);
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
            Log::error('ReportAttachmentController@store - Database insertion failed');
            return response()->json(['error' => 'Database insertion failed'], 500);
        }

        Log::info('ReportAttachmentController@store - File uploaded and database updated', ['file_name' => $filename, 'report_id' => $report->report_id]);
        return $attachment;
    }

    public function downloadAttachment($id)
    {
        try {
            $attachment = ReportAttachment::findOrFail($id);
            $path = $attachment->file_path;

            if (!Storage::exists($path)) {
                abort(404, 'File not found.');
            }

            return Storage::download($path, $attachment->file_name);
        } catch (\Exception $e) {
            Log::error('Failed to download report attachment', ['error' => $e->getMessage()]);
            return back()->withErrors('Failed to download the file.');
        }
    }

    public function update(Request $request, $report_id)
    {
        Log::info('ReportAttachmentController@update - Data received', ['data' => $request->all(), 'report_id' => $report_id]);

        $report = DPReport::where('report_id', $report_id)->firstOrFail();

        if (!$request->hasFile('file')) {
            Log::info('ReportAttachmentController@update - No new file uploaded');
            return response()->json(['message' => 'No new file uploaded, existing files retained'], 200);
        }

        if (!$request->file('file')->isValid()) {
            Log::error('ReportAttachmentController@update - Invalid file upload');
            return response()->json(['error' => 'Invalid file upload'], 400);
        }

        $file = $request->file('file');
        $filename = $request->input('file_name', 'default_filename') . '.' . $file->getClientOriginalExtension();
        $filename = str_replace(' ', '_', $filename);

        $path = $file->storeAs('public/report_attachments', $filename);
        if (!$path) {
            Log::error('ReportAttachmentController@update - File storage failed');
            return response()->json(['error' => 'File storage failed'], 500);
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
            Log::error('ReportAttachmentController@update - Database insertion failed');
            return response()->json(['error' => 'Database insertion failed'], 500);
        }

        Log::info('ReportAttachmentController@update - New attachment added and database updated', ['file_name' => $filename, 'report_id' => $report->report_id]);
        return $attachment;
    }
}

