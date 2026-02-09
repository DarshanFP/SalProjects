<?php

namespace App\Services\Attachment;

/**
 * Value object returned by ProjectAttachmentHandler.
 * Errors are returned via this object; no exceptions for validation/storage failures.
 */
final class AttachmentResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?object $attachmentRecord,
        public readonly array $errorsByField = [],
        public readonly array $storedPaths = [],
        public readonly array $storedFileIds = [],
    ) {}

    public static function success(object $attachmentRecord, array $storedPaths = [], array $storedFileIds = []): self
    {
        return new self(
            success: true,
            attachmentRecord: $attachmentRecord,
            errorsByField: [],
            storedPaths: $storedPaths,
            storedFileIds: $storedFileIds,
        );
    }

    public static function failure(array $errorsByField, array $storedPaths = []): self
    {
        return new self(
            success: false,
            attachmentRecord: null,
            errorsByField: $errorsByField,
            storedPaths: $storedPaths,
            storedFileIds: [],
        );
    }

    /** Flatten errors for response (e.g. first error per field or all). */
    public function getFlattenedErrors(): array
    {
        $flat = [];
        foreach ($this->errorsByField as $field => $messages) {
            foreach ((array) $messages as $msg) {
                $flat[] = $msg;
            }
        }
        return $flat;
    }
}
