<?php

namespace App\Http\Controllers\Projects\IES;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\IES\ProjectIESAttachments;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Projects\IES\StoreIESAttachmentsRequest;
use App\Http\Requests\Projects\IES\UpdateIESAttachmentsRequest;
use App\Helpers\LogHelper;

class IESAttachmentsController extends Controller
{
    // 🟢 STORE ATTACHMENTS
    public function store(FormRequest $request, $projectId)
    {
        // Use all() to get all form data including fields not in StoreProjectRequest/UpdateProjectRequest validation rules
        $validated = $request->all();
        
        DB::beginTransaction();

        try {
            Log::info('Storing IES attachments', ['project_id' => $projectId]);

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

    public function update(FormRequest $request, $projectId)
    {
        // Use all() to get all form data including fields not in StoreProjectRequest/UpdateProjectRequest validation rules
        $validated = $request->all();
        
        DB::beginTransaction();
        try {
            Log::info('Starting update process for IES Attachments', [
                'project_id' => $projectId
            ]);

            // Handle file uploads and database updates
            ProjectIESAttachments::handleAttachments($request, $projectId);

            DB::commit();

            Log::info('IES Attachments updated successfully', [
                'project_id' => $projectId
            ]);
            LogHelper::logSafeRequest('Files received for update', $request, [
                'project_id' => $projectId,
            ]);


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
