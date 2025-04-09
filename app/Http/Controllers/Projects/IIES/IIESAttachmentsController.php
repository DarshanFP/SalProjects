<?php

namespace App\Http\Controllers\Projects\IIES;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IIES\ProjectIIESAttachments;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class IIESAttachmentsController extends Controller
{
    /**
     * STORE: handle initial file uploads for IIES Attachments.
     */
    public function store(Request $request, $projectId)
    {
        Log::info('IIESAttachmentsController@store - Start', [
            'project_id' => $projectId,
            'request_data' => $request->all(),
        ]);

        DB::beginTransaction();
        try {
            // Validate file fields (optional).
            $request->validate($this->validationRules());

            // Ensure project exists
            if (!Project::where('project_id', $projectId)->exists()) {
                return response()->json(['error' => 'Project not found.'], 404);
            }

            // Model’s static handleAttachments(...) does the actual file + DB work
            $attachments = ProjectIIESAttachments::handleAttachments($request, $projectId);

            DB::commit();
            Log::info('IIESAttachmentsController@store - Success', [
                'project_id' => $projectId,
                'attachment_id' => $attachments->IIES_attachment_id ?? null,
            ]);

            return response()->json([
                'message' => 'IIES attachments stored successfully.',
                'attachments' => $attachments
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IIESAttachmentsController@store - Error', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to store IIES attachments.'], 500);
        }
    }

    /**
     * SHOW: retrieve existing attachments for a project.
     */
    public function show($projectId)
    {
        try {
            Log::info('IIESAttachmentsController@show - Fetching attachments', ['project_id' => $projectId]);

            // Fetch attachments for the given project ID
            $attachments = ProjectIIESAttachments::where('project_id', $projectId)->first();

            if (!$attachments) {
                Log::warning('IIESAttachmentsController@show - No attachments found', ['project_id' => $projectId]);
                return []; // ✅ Always return an empty array instead of null
            }

            return [
                'iies_aadhar_card'          => $attachments->iies_aadhar_card ? Storage::url($attachments->iies_aadhar_card) : null,
                'iies_fee_quotation'        => $attachments->iies_fee_quotation ? Storage::url($attachments->iies_fee_quotation) : null,
                'iies_scholarship_proof'    => $attachments->iies_scholarship_proof ? Storage::url($attachments->iies_scholarship_proof) : null,
                'iies_medical_confirmation' => $attachments->iies_medical_confirmation ? Storage::url($attachments->iies_medical_confirmation) : null,
                'iies_caste_certificate'    => $attachments->iies_caste_certificate ? Storage::url($attachments->iies_caste_certificate) : null,
                'iies_self_declaration'     => $attachments->iies_self_declaration ? Storage::url($attachments->iies_self_declaration) : null,
                'iies_death_certificate'    => $attachments->iies_death_certificate ? Storage::url($attachments->iies_death_certificate) : null,
                'iies_request_letter'       => $attachments->iies_request_letter ? Storage::url($attachments->iies_request_letter) : null,
            ];
        } catch (\Exception $e) {
            Log::error('IIESAttachmentsController@show - Error retrieving attachments', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);

            return []; // ✅ Always return an array, even on error
        }
    }


    /**
     * EDIT: return either a Blade view or JSON to allow editing.
     */
    public function edit($projectId)
    {
        Log::info('IIESAttachmentsController@edit - Start', ['project_id' => $projectId]);

        try {
            // Load the project + its IIESAttachments (if any)
            $attachments = ProjectIIESAttachments::where('project_id', $projectId)->first();

            if ($attachments) {
                Log::info('IIESAttachmentsController@edit - Attachments found', [
                    'project_id' => $projectId,
                    'attachment_id' => $attachments->IIES_attachment_id,
                    'stored_files' => $attachments->toArray(), // Logs all stored file paths
                ]);
            } else {
                Log::warning('IIESAttachmentsController@edit - No attachments found', ['project_id' => $projectId]);
            }

            // Log what data is being sent to the main controller
            Log::info('IIESAttachmentsController@edit - Data sent to ProjectController', [
                'attachments' => $attachments ? $attachments->toArray() : null,
            ]);

            return $attachments;
        } catch (\Exception $e) {
            Log::error('IIESAttachmentsController@edit - Error', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to retrieve IIES attachments.'], 500);
        }
    }


    /**
     * UPDATE: handle new file uploads that overwrite old files, if present.
     */
    public function update(Request $request, $projectId)
    {
        Log::info('IIESAttachmentsController@update - Start', [
            'project_id' => $projectId,
            'request_data' => $request->all()
        ]);

        DB::beginTransaction();
        try {
            // Validate new files (optional)
            $request->validate($this->validationRules());

            $attachments = ProjectIIESAttachments::handleAttachments($request, $projectId);

            DB::commit();
            Log::info('IIESAttachmentsController@update - Success', [
                'project_id' => $projectId,
                'attachment_id' => $attachments->IIES_attachment_id ?? null
            ]);

            // If you prefer returning JSON:
            return response()->json([
                'message' => 'IIES attachments updated successfully.',
                'attachments' => $attachments
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IIESAttachmentsController@update - Error', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to update IIES attachments.'], 500);
        }
    }

    /**
     * DESTROY: remove the IIESAttachments record (and any stored files).
     */
    public function destroy($projectId)
    {
        Log::info('IIESAttachmentsController@destroy - Start', ['project_id' => $projectId]);

        DB::beginTransaction();
        try {
            $attachments = ProjectIIESAttachments::where('project_id', $projectId)->firstOrFail();

            Log::info('Deleting attachments record', [
                'attachment_id' => $attachments->IIES_attachment_id
            ]);

            // Remove the directory & files from storage
            Storage::deleteDirectory("project_attachments/IIES/{$projectId}");
            $attachments->delete();

            DB::commit();
            return response()->json(['message' => 'IIES attachments deleted successfully.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IIESAttachmentsController@destroy - Error', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to delete IIES attachments.'], 500);
        }
    }

    /**
     * Validation rules for each file input.
     */
    private function validationRules(): array
    {
        return [
            'iies_aadhar_card'          => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'iies_fee_quotation'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'iies_scholarship_proof'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'iies_medical_confirmation' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'iies_caste_certificate'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'iies_self_declaration'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'iies_death_certificate'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'iies_request_letter'       => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ];
    }
}
