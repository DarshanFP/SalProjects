<?php

namespace App\Helpers;

use App\Models\OldProjects\IES\ProjectIESAttachmentFile;
use App\Models\OldProjects\IIES\ProjectIIESAttachmentFile;
use App\Models\OldProjects\IAH\ProjectIAHDocumentFile;
use App\Models\OldProjects\ILP\ProjectILPDocumentFile;
use App\Models\OldProjects\ProjectAttachment;
use App\Models\Reports\Monthly\ReportAttachment;

/**
 * Helper class for generating file names according to the pattern:
 * {ProjectID}_{FieldName}_{serial}.{extension}
 * 
 * If user provides a custom name, it will be used instead.
 */
class AttachmentFileNamingHelper
{
    /**
     * Generate file name based on pattern or user-provided name
     * 
     * @param string $projectId
     * @param string $fieldName
     * @param string $extension
     * @param string|null $userProvidedName Optional user-provided name
     * @param string $attachmentType IES, IIES, IAH, ILP, project, report
     * @return string Generated file name
     */
    public static function generateFileName($projectId, $fieldName, $extension, $userProvidedName = null, $attachmentType = 'project')
    {
        // If user provided a name, use it (but sanitize it)
        if (!empty($userProvidedName)) {
            $sanitized = self::sanitizeFilename($userProvidedName);
            // Ensure it has the correct extension
            if (!str_ends_with(strtolower($sanitized), '.' . strtolower($extension))) {
                $sanitized .= '.' . $extension;
            }
            return $sanitized;
        }

        // Generate name using pattern: {ProjectID}_{FieldName}_{serial}.{extension}
        $serialNumber = self::getNextSerialNumber($projectId, $fieldName, $attachmentType);
        $serialFormatted = str_pad($serialNumber, 2, '0', STR_PAD_LEFT);
        
        // Clean field name (remove prefixes like 'iies_', 'ies_', etc.)
        $cleanFieldName = self::cleanFieldName($fieldName);
        
        return "{$projectId}_{$cleanFieldName}_{$serialFormatted}.{$extension}";
    }

    /**
     * Get next serial number for a field
     * 
     * @param string $projectId
     * @param string $fieldName
     * @param string $attachmentType
     * @return int Next serial number
     */
    public static function getNextSerialNumber($projectId, $fieldName, $attachmentType = 'project')
    {
        $modelClass = self::getModelClass($attachmentType);
        
        if (!$modelClass) {
            return 1; // Default to 1 if model not found
        }
        
        $lastFile = $modelClass::where('project_id', $projectId)
            ->where('field_name', $fieldName)
            ->orderBy('serial_number', 'desc')
            ->first();
        
        if ($lastFile && is_numeric($lastFile->serial_number)) {
            return (int)$lastFile->serial_number + 1;
        }
        
        return 1;
    }

    /**
     * Clean field name by removing common prefixes
     * 
     * @param string $fieldName
     * @return string Cleaned field name
     */
    private static function cleanFieldName($fieldName)
    {
        // Remove common prefixes
        $fieldName = str_replace('iies_', '', $fieldName);
        $fieldName = str_replace('ies_', '', $fieldName);
        $fieldName = str_replace('_doc', '', $fieldName);
        $fieldName = str_replace('_copy', '', $fieldName);
        
        return $fieldName;
    }

    /**
     * Get model class based on attachment type
     * 
     * @param string $attachmentType
     * @return string|null Model class name
     */
    private static function getModelClass($attachmentType)
    {
        $models = [
            'IES' => ProjectIESAttachmentFile::class,
            'IIES' => ProjectIIESAttachmentFile::class,
            'IAH' => ProjectIAHDocumentFile::class,
            'ILP' => ProjectILPDocumentFile::class,
            'project' => ProjectAttachment::class,
            'report' => ReportAttachment::class,
        ];
        
        return $models[$attachmentType] ?? null;
    }

    /**
     * Get file icon based on file path/extension
     * 
     * @param string $filePath
     * @return string Icon class
     */
    public static function getFileIcon($filePath)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $icons = config('attachments.file_icons', []);
        return $icons[$extension] ?? $icons['default'] ?? 'fas fa-file text-secondary';
    }

    /**
     * Sanitize filename to prevent path traversal
     * 
     * @param string $filename
     * @return string Sanitized filename
     */
    public static function sanitizeFilename($filename)
    {
        // Remove any path separators and dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '_', $filename);
        $filename = trim($filename, '._');
        
        if (empty($filename)) {
            $filename = 'file';
        }
        
        return $filename;
    }
}
