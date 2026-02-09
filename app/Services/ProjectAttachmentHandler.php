<?php

namespace App\Services;

use App\Helpers\AttachmentFileNamingHelper;
use App\Services\Attachment\AttachmentContext;
use App\Services\Attachment\AttachmentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Centralized attachment handler for project uploads.
 * Validates, stores, and creates file records. Returns AttachmentResult; no exceptions for validation/storage failures.
 */
class ProjectAttachmentHandler
{
    public static function handle(Request $request, string $projectId, AttachmentContext $context, array $fieldConfig): AttachmentResult
    {
        $projectDir = "project_attachments/{$context->storagePrefix}/{$projectId}";
        Storage::disk('public')->makeDirectory($projectDir);

        $attachmentModel = $context->attachmentModelClass;
        $attachments = $attachmentModel::updateOrCreate(['project_id' => $projectId], []);

        $uploadedFiles = [];
        $errorsByField = [];
        $maxSize = config('attachments.max_file_size.server_bytes');
        $allowedTypes = config('attachments.allowed_file_types.image_only');
        $prefix = $context->requestKeyPrefix;

        foreach ($fieldConfig as $field => $config) {
            $key = $prefix ? $prefix . $field : $field;
            if (!$request->hasFile($key)) {
                continue;
            }

            $files = $request->file($key);
            $files = is_array($files) ? $files : [$files];

            $namesKey = $prefix ? $prefix . $field . '_names' : $field . '_names';
            $descriptionsKey = $prefix ? $prefix . $field . '_descriptions' : $field . '_descriptions';
            $fileNames = $request->input($namesKey, []);
            $descriptions = $request->input($descriptionsKey, []);

            if (!is_array($fileNames)) {
                $fileNames = [];
            }
            if (!is_array($descriptions)) {
                $descriptions = [];
            }

            $fieldErrors = [];
            $fieldStored = [];

            foreach ($files as $index => $file) {
                if (!$file || !$file->isValid()) {
                    continue;
                }

                if (!self::isValidFileType($file, $allowedTypes)) {
                    Log::error('Invalid file type for attachment', [
                        'field' => $field,
                        'mime_type' => $file->getMimeType(),
                        'extension' => $file->getClientOriginalExtension(),
                    ]);
                    $typesList = implode(', ', array_map('strtoupper', $allowedTypes['extensions']));
                    $errorMsg = str_replace(':types', $typesList, config('attachments.messages.file_type_error'));
                    $fieldErrors[] = "Invalid file type for {$field}. {$errorMsg}";
                    break;
                }

                if ($file->getSize() > $maxSize) {
                    $maxSizeMB = config('attachments.max_file_size.display_mb');
                    $errorMsg = str_replace(':size', (string) $maxSizeMB, config('attachments.messages.file_size_error'));
                    $fieldErrors[] = "File size exceeds limit for {$field}. {$errorMsg}";
                    break;
                }

                $userProvidedName = $fileNames[$index] ?? null;
                $extension = $file->getClientOriginalExtension();
                $fileName = AttachmentFileNamingHelper::generateFileName(
                    $projectId,
                    $field,
                    $extension,
                    $userProvidedName,
                    $context->storagePrefix
                );

                $filePath = $file->storeAs($projectDir, $fileName, 'public');

                if (!$filePath || !Storage::disk('public')->exists($filePath)) {
                    $fieldErrors[] = config('attachments.error_messages.storage_failed', 'Failed to save file to storage.');
                    break;
                }

                $fieldStored[] = $filePath;

                $serialNumber = AttachmentFileNamingHelper::getNextSerialNumber($projectId, $field, $context->storagePrefix);
                $serialFormatted = str_pad($serialNumber, 2, '0', STR_PAD_LEFT);

                $fileModel = $context->fileModelClass;
                $fileModel::create([
                    $context->attachmentIdColumn => $attachments->{$context->attachmentIdColumn},
                    'project_id' => $projectId,
                    'field_name' => $field,
                    'file_path' => $filePath,
                    'file_name' => $userProvidedName ?? $fileName,
                    'description' => $descriptions[$index] ?? '',
                    'serial_number' => $serialFormatted,
                    'public_url' => Storage::url($filePath),
                ]);
            }

            if (!empty($fieldErrors)) {
                foreach ($fieldStored as $p) {
                    if (Storage::disk('public')->exists($p)) {
                        Storage::disk('public')->delete($p);
                    }
                }
                foreach ($uploadedFiles as $p) {
                    if (Storage::disk('public')->exists($p)) {
                        Storage::disk('public')->delete($p);
                    }
                }
                $errorsByField[$field] = $fieldErrors;
                return AttachmentResult::failure($errorsByField, []);
            }

            $uploadedFiles = array_merge($uploadedFiles, $fieldStored);
        }

        $attachments->save();

        return AttachmentResult::success($attachments, $uploadedFiles);
    }

    private static function isValidFileType($file, array $allowedTypes): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();

        return in_array($extension, $allowedTypes['extensions']) &&
               in_array($mimeType, $allowedTypes['mimes']);
    }
}
