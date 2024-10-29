<?php

namespace App\Http\Controllers\Projects\IES;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IES\ProjectIESAttachments;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class IESAttachmentsController extends Controller
{
    // Store or update attachments for a project
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing IES attachments', ['project_id' => $projectId]);

            // Find or create a new attachments record
            $attachments = ProjectIESAttachments::where('project_id', $projectId)->first() ?: new ProjectIESAttachments();
            $attachments->project_id = $projectId;

            // Handle each file upload
            foreach (['aadhar_card', 'fee_quotation', 'scholarship_proof', 'medical_confirmation', 'caste_certificate', 'self_declaration', 'death_certificate', 'request_letter'] as $fileField) {
                if ($request->hasFile($fileField)) {
                    // Store the file and save the file path
                    $filePath = $request->file($fileField)->storeAs('project_attachments/' . $projectId, $request->file($fileField)->getClientOriginalName());
                    $attachments->{$fileField} = $filePath;
                }
            }

            $attachments->save();

            DB::commit();
            Log::info('IES attachments saved successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IES attachments saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IES attachments', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save IES attachments.'], 500);
        }
    }

    // Show attachments for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching IES attachments', ['project_id' => $projectId]);

            $attachments = ProjectIESAttachments::where('project_id', $projectId)->firstOrFail();
            return response()->json($attachments, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching IES attachments', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch IES attachments.'], 500);
        }
    }

    // Edit attachments for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing IES attachments', ['project_id' => $projectId]);

            $attachments = ProjectIESAttachments::where('project_id', $projectId)->firstOrFail();

            // Return the data directly
            return $attachments;
        } catch (\Exception $e) {
            Log::error('Error editing IES attachments', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Update attachments for a project
    public function update(Request $request, $projectId)
    {
        return $this->store($request, $projectId); // Reuse the store logic for update
    }

    // Delete attachments for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting IES attachments', ['project_id' => $projectId]);

            $attachments = ProjectIESAttachments::where('project_id', $projectId)->firstOrFail();
            foreach (['aadhar_card', 'fee_quotation', 'scholarship_proof', 'medical_confirmation', 'caste_certificate', 'self_declaration', 'death_certificate', 'request_letter'] as $fileField) {
                if (Storage::exists($attachments->{$fileField})) {
                    Storage::delete($attachments->{$fileField});
                }
            }
            $attachments->delete();

            DB::commit();
            Log::info('IES attachments deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IES attachments deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IES attachments', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete IES attachments.'], 500);
        }
    }
}
