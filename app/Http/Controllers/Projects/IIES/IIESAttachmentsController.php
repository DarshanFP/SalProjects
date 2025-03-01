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
    // public function show($projectId)
    // {
    //     Log::info('IIESAttachmentsController@show - Start', ['project_id' => $projectId]);
    //     try {
    //         $attachments = ProjectIIESAttachments::where('project_id', $projectId)->firstOrFail();

    //         // Option A: return DB record
    //         // return response()->json($attachments, 200);

    //         // Option B: also return publicly accessible URLs for each file
    //         $fields = [
    //             'iies_aadhar_card',
    //             'iies_fee_quotation',
    //             'iies_scholarship_proof',
    //             'iies_medical_confirmation',
    //             'iies_caste_certificate',
    //             'iies_self_declaration',
    //             'iies_death_certificate',
    //             'iies_request_letter'
    //         ];
    //         $urls = [];
    //         foreach ($fields as $field) {
    //             if (!empty($attachments->$field)) {
    //                 $urls[$field] = Storage::url($attachments->$field);
    //             }
    //         }

    //         return response()->json([
    //             'attachments' => $urls
    //         ], 200);

    //     } catch (\Exception $e) {
    //         Log::error('IIESAttachmentsController@show - Error', [
    //             'project_id' => $projectId,
    //             'error' => $e->getMessage()
    //         ]);
    //         return response()->json(['error' => 'Failed to fetch IIES attachments.'], 500);
    //     }
    // }

    public function show($projectId)
{
    Log::info('IIESAttachmentsController@show - Start', ['project_id' => $projectId]);

    try {
        // Attempt to load existing attachments for the given project
        $IIESAttachments = ProjectIIESAttachments::where('project_id', $projectId)->first();

        if ($IIESAttachments) {
            Log::info('IIESAttachmentsController@show - Attachments found', [
                'project_id'    => $projectId,
                'attachment_id' => $IIESAttachments->IIES_attachment_id,
                'stored_files'  => $IIESAttachments->toArray(),
            ]);
        } else {
            Log::warning('IIESAttachmentsController@show - No attachments found', ['project_id' => $projectId]);
        }

        // Return the model or null so the ProjectController can pass it to the Blade
        return $IIESAttachments;
    } catch (\Exception $e) {
        Log::error('IIESAttachmentsController@show - Error retrieving attachments', [
            'project_id' => $projectId,
            'error'      => $e->getMessage(),
        ]);
        // Return null or handle the exception as needed
        return null;
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
