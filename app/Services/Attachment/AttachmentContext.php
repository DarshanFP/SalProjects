<?php

namespace App\Services\Attachment;

/**
 * Module context for ProjectAttachmentHandler.
 * Supplies storage prefix, model classes, and request key prefix.
 */
final class AttachmentContext
{
    public function __construct(
        public readonly string $storagePrefix,
        public readonly string $attachmentModelClass,
        public readonly string $fileModelClass,
        public readonly string $attachmentIdColumn,
        public readonly string $requestKeyPrefix = '',
    ) {}

    public static function forIES(): self
    {
        return new self(
            storagePrefix: 'IES',
            attachmentModelClass: \App\Models\OldProjects\IES\ProjectIESAttachments::class,
            fileModelClass: \App\Models\OldProjects\IES\ProjectIESAttachmentFile::class,
            attachmentIdColumn: 'IES_attachment_id',
            requestKeyPrefix: '',
        );
    }

    public static function forIIES(): self
    {
        return new self(
            storagePrefix: 'IIES',
            attachmentModelClass: \App\Models\OldProjects\IIES\ProjectIIESAttachments::class,
            fileModelClass: \App\Models\OldProjects\IIES\ProjectIIESAttachmentFile::class,
            attachmentIdColumn: 'IIES_attachment_id',
            requestKeyPrefix: '',
        );
    }

    public static function forIAH(): self
    {
        return new self(
            storagePrefix: 'IAH',
            attachmentModelClass: \App\Models\OldProjects\IAH\ProjectIAHDocuments::class,
            fileModelClass: \App\Models\OldProjects\IAH\ProjectIAHDocumentFile::class,
            attachmentIdColumn: 'IAH_doc_id',
            requestKeyPrefix: '',
        );
    }

    public static function forILP(): self
    {
        return new self(
            storagePrefix: 'ILP',
            attachmentModelClass: \App\Models\OldProjects\ILP\ProjectILPAttachedDocuments::class,
            fileModelClass: \App\Models\OldProjects\ILP\ProjectILPDocumentFile::class,
            attachmentIdColumn: 'ILP_doc_id',
            requestKeyPrefix: 'attachments.',
        );
    }
}
