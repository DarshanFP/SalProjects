<?php

namespace App\Http\Controllers\Projects\IES;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IES\ProjectIESAttachments;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IESAttachmentsController extends Controller
{
    // 🟢 STORE ATTACHMENTS
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();

        try {
            Log::info('Storing IES attachments', ['project_id' => $projectId]);

            $validatedData = $request->validate([
                'aadhar_card' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'fee_quotation' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'scholarship_proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'medical_confirmation' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'caste_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'self_declaration' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'death_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'request_letter' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ]);

            ProjectIESAttachments::handleAttachments($request, $projectId);

            DB::commit();
            return response()->json(['message' => 'IES attachments saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IES attachments', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save attachments.'], 500);
        }
    }

    // 🟠 SHOW ATTACHMENTS
    public function show($projectId)
{
    try {
        Log::info('Fetching IES attachments', ['project_id' => $projectId]);

        // Retrieve the attachment details
        $attachments = ProjectIESAttachments::where('project_id', $projectId)->first();

        if (!$attachments) {
            return null; // If no attachments are found, return null so Blade can handle it properly
        }

        return $attachments; // Return as an object for use in the Blade view
    } catch (\Exception $e) {
        Log::error('Error fetching IES attachments', ['error' => $e->getMessage()]);
        return null; // Return null to prevent errors in the Blade template
    }
}


    // 🟡 EDIT ATTACHMENTS 
    public function edit($projectId)
    {
        try {
            $attachments = ProjectIESAttachments::where('project_id', $projectId)->firstOrFail();

            // Log the retrieved data
            Log::info('Fetched IES attachments for editing', [
                'project_id' => $projectId,
                'attachments' => $attachments
            ]);

            // ✅ Return the raw Eloquent object
            return $attachments;

        } catch (\Exception $e) {
            Log::error('Error fetching IES attachments for editing', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);

            // If you really want to handle the exception, you could
            // return null or throw to the ProjectController
            return null;
        }
    }

    public function update(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Starting update process for IES Attachments', [
                'project_id' => $projectId,
                'request_data' => $request->all()
            ]);

            // Validate request inputs
            $validatedData = $request->validate([
                'aadhar_card' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'fee_quotation' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'scholarship_proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'medical_confirmation' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'caste_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'self_declaration' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'death_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'request_letter' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ]);

            Log::info('Validation passed for IES Attachments update', [
                'project_id' => $projectId,
                'validated_data' => $validatedData
            ]);

            // Handle file uploads and database updates
            ProjectIESAttachments::handleAttachments($request, $projectId);

            DB::commit();

            Log::info('IES Attachments updated successfully', [
                'project_id' => $projectId
            ]);
            Log::info('Files received for update:', $request->all());


            return response()->json(['message' => 'IES Attachments updated successfully.'], 200);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Error updating IES Attachments', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Failed to update IES Attachments.'], 500);
        }
    }



    // 🔵 DESTROY ATTACHMENTS
    public function destroy($projectId)
    {
        DB::beginTransaction();

        try {
            $attachments = ProjectIESAttachments::where('project_id', $projectId)->firstOrFail();
            \Storage::deleteDirectory("project_attachments/IES/{$projectId}");
            $attachments->delete();

            DB::commit();
            return response()->json(['message' => 'IES attachments deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete attachments.'], 500);
        }
    }
}
