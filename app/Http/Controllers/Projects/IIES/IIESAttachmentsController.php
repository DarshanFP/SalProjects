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
    //     Log::info('IIESAttachmentsController@show - Fetching attachments', ['project_id' => $projectId]);

    //     try {
    //         // Fetch attachments related to the project
    //         $attachments = ProjectIIESAttachments::where('project_id', $projectId)->first();

    //         if (!$attachments) {
    //             Log::warning('IIESAttachmentsController@show - No attachments found', ['project_id' => $projectId]);
    //             return null; // Return null so Blade can handle "No attachments" scenario
    //         }

    //         Log::info('IIESAttachmentsController@show - Attachments found', [
    //             'project_id' => $projectId,
    //             'attachments' => $attachments->toArray(),
    //         ]);

    //         return $attachments; // Return as an object to be used in the Blade view
    //     } catch (\Exception $e) {
    //         Log::error('IIESAttachmentsController@show - Error retrieving attachments', [
    //             'project_id' => $projectId,
    //             'error' => $e->getMessage(),
    //         ]);

    //         return null; // Prevents Blade from breaking in case of an error
    //     }
    // }

    // public function show($projectId)
    // {
    //     try {
    //         Log::info('IIESAttachmentsController@show - Fetching attachments', ['project_id' => $projectId]);

    //         // Fetch the single row containing all IIES attachments
    //         $attachments = ProjectIIESAttachments::where('project_id', $projectId)->first();

    //         if (!$attachments) {
    //             // If no attachments are found, return `null` so the Blade can handle the "not found" gracefully
    //             return null;
    //         }

    //         return $attachments;
    //     } catch (\Exception $e) {
    //         Log::error('IIESAttachmentsController@show - Error fetching attachments', [
    //             'project_id' => $projectId,
    //             'error' => $e->getMessage(),
    //         ]);

    //         // Return null or handle as needed
    //         return null;
    //     }
    // }
// app/Http/Controllers/Projects/IIES/IIESAttachmentsController.php

public function show($projectId)
{
    // Grab the single attachments row for the project:
    $attachments = ProjectIIESAttachments::where('project_id', $projectId)->first();

    // If no attachments, return null so the Blade can handle it gracefully.
    return $attachments;
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
