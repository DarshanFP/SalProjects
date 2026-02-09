<?php

namespace App\Http\Controllers\Projects\IES;

use App\Http\Controllers\Controller;
use App\Helpers\LogHelper;
use App\Services\Attachment\AttachmentContext;
use App\Services\ProjectAttachmentHandler;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IESAttachmentsController extends Controller
{
    private const IES_FIELDS = [
        'aadhar_card', 'fee_quotation', 'scholarship_proof', 'medical_confirmation',
        'caste_certificate', 'self_declaration', 'death_certificate', 'request_letter',
    ];

    /** @return array<string, array> */
    private static function iesFieldConfig(): array
    {
        return array_fill_keys(self::IES_FIELDS, []);
    }

    // 🟢 STORE ATTACHMENTS
    public function store(FormRequest $request, $projectId)
    {
        DB::beginTransaction();

        try {
            Log::info('Storing IES attachments', ['project_id' => $projectId]);

            $result = ProjectAttachmentHandler::handle(
                $request,
                (string) $projectId,
                AttachmentContext::forIES(),
                self::iesFieldConfig()
            );

            if (!$result->success) {
                DB::rollBack();
                Log::warning('IES attachment validation failed', [
                    'project_id' => $projectId,
                    'errors' => $result->errorsByField,
                ]);
                return response()->json([
                    'error' => 'Failed to save attachments.',
                    'errors' => $result->errorsByField,
                ], 422);
            }

            DB::commit();
            return response()->json(['message' => 'IES attachments saved successfully.'], 200);
        } catch (\Throwable $e) {
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

            $attachments = \App\Models\OldProjects\IES\ProjectIESAttachments::where('project_id', $projectId)->first();

            if (!$attachments) {
                return null;
            }

            return $attachments;
        } catch (\Exception $e) {
            Log::error('Error fetching IES attachments', ['error' => $e->getMessage()]);
            return null;
        }
    }


    // 🟡 EDIT ATTACHMENTS
    public function edit($projectId)
    {
        try {
            $attachments = \App\Models\OldProjects\IES\ProjectIESAttachments::where('project_id', $projectId)->firstOrFail();

            Log::info('Fetched IES attachments for editing', [
                'project_id' => $projectId,
                'attachments' => $attachments
            ]);

            return $attachments;
        } catch (\Exception $e) {
            Log::error('Error fetching IES attachments for editing', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function update(FormRequest $request, $projectId)
    {
        DB::beginTransaction();

        try {
            Log::info('Starting update process for IES Attachments', ['project_id' => $projectId]);

            $result = ProjectAttachmentHandler::handle(
                $request,
                (string) $projectId,
                AttachmentContext::forIES(),
                self::iesFieldConfig()
            );

            if (!$result->success) {
                DB::rollBack();
                Log::warning('IES attachment validation failed on update', [
                    'project_id' => $projectId,
                    'errors' => $result->errorsByField,
                ]);
                return response()->json([
                    'error' => 'Failed to update IES Attachments.',
                    'errors' => $result->errorsByField,
                ], 422);
            }

            DB::commit();

            Log::info('IES Attachments updated successfully', ['project_id' => $projectId]);
            LogHelper::logSafeRequest('Files received for update', $request, [
                'project_id' => $projectId,
            ]);

            return response()->json(['message' => 'IES Attachments updated successfully.'], 200);
        } catch (\Throwable $e) {
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
            $attachments = \App\Models\OldProjects\IES\ProjectIESAttachments::where('project_id', $projectId)->firstOrFail();
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
