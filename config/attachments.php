<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Attachment Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for file attachments across the application.
    | This includes file size limits, allowed file types, and storage settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | File Size Limits
    |--------------------------------------------------------------------------
    |
    | Maximum file size in bytes for different attachment types.
    | Note: Display limit is shown to users, but server accepts up to max_size
    | to provide a buffer for files slightly over the display limit.
    |
    */
    'max_file_size' => [
        'server_bytes' => 7340032, // 7MB in bytes (server-side limit)
        'display_mb' => 5,         // 5MB for user display
    ],
    // Legacy support (deprecated, use max_file_size instead)
    'max_size' => 7340032, // 7MB in bytes (server-side limit)
    'display_max_size' => 5242880, // 5MB in bytes (shown to users)
    'max_size_mb' => 7, // 7MB for error messages
    'display_max_size_mb' => 5, // 5MB for user display

    /*
    |--------------------------------------------------------------------------
    | Allowed File Types
    |--------------------------------------------------------------------------
    |
    | File types allowed for different attachment categories.
    | Each category can have different allowed types.
    |
    */
    'allowed_file_types' => [
        'general' => [
            'extensions' => ['pdf', 'doc', 'docx'],
            'mimes' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        ],
        'image_only' => [
            'extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
            'mimes' => [
                'application/pdf',
                'image/jpeg',
                'image/png',
            ],
        ],
    ],
    // Legacy support (deprecated, use allowed_file_types instead)
    'allowed_types' => [
        'project_attachments' => [
            'extensions' => ['pdf', 'doc', 'docx'],
            'mime_types' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ],
        ],
        'ies_attachments' => [
            'extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
            'mime_types' => [
                'application/pdf',
                'image/jpeg',
                'image/png'
            ],
        ],
        'iies_attachments' => [
            'extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
            'mime_types' => [
                'application/pdf',
                'image/jpeg',
                'image/png'
            ],
        ],
        'iah_documents' => [
            'extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
            'mime_types' => [
                'application/pdf',
                'image/jpeg',
                'image/png'
            ],
        ],
        'ilp_documents' => [
            'extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
            'mime_types' => [
                'application/pdf',
                'image/jpeg',
                'image/png'
            ],
        ],
        'report_attachments' => [
            'extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'],
            'mime_types' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'image/jpeg',
                'image/png'
            ],
        ],
        'problem_tree' => [
            'extensions' => ['jpg', 'jpeg', 'png'],
            'mime_types' => [
                'image/jpeg',
                'image/png',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Problem Tree Image Optimization
    |--------------------------------------------------------------------------
    | Resize (longest side) and re-encode as JPEG to reduce file size.
    */
    'problem_tree_optimization' => [
        'enabled' => true,
        'max_dimension' => 1920,
        'jpeg_quality' => 85,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Type Icons
    |--------------------------------------------------------------------------
    |
    | Font Awesome icon classes for different file types.
    | Used for displaying file type indicators in the UI.
    |
    */
    'file_icons' => [
        'pdf' => 'fas fa-file-pdf text-danger',
        'doc' => 'fas fa-file-word text-primary',
        'docx' => 'fas fa-file-word text-primary',
        'xls' => 'fas fa-file-excel text-success',
        'xlsx' => 'fas fa-file-excel text-success',
        'jpg' => 'fas fa-file-image text-info',
        'jpeg' => 'fas fa-file-image text-info',
        'png' => 'fas fa-file-image text-info',
        'default' => 'fas fa-file text-secondary',
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Settings
    |--------------------------------------------------------------------------
    |
    | Storage paths and settings for different attachment types.
    |
    */
    'storage' => [
        'disk' => 'public',
        'base_path' => 'project_attachments',
        'permissions' => 0755,
        'recursive' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Messages
    |--------------------------------------------------------------------------
    |
    | User-friendly error messages for different validation scenarios.
    |
    */
    'messages' => [
        'file_type_error' => 'Invalid file type. Only :types files are allowed.',
        'file_size_error' => 'File size must not exceed :size MB.',
        'file_not_found' => 'File not found in storage.',
        'invalid_upload' => 'Invalid file upload detected.',
        'upload_success' => 'Attachment uploaded successfully.',
        'replace_success' => 'Attachment replaced successfully.',
        'download_failed' => 'Failed to download the file.',
        'attachment_not_found' => 'Attachment not found.',
    ],
    // Legacy support (deprecated, use messages instead)
    'error_messages' => [
        'file_type_invalid' => 'Invalid file type. Only :types files are allowed.',
        'file_size_exceeded' => 'File size must not exceed :size MB.',
        'file_size_exceeded_server' => 'File size exceeds :size MB limit.',
        'file_not_found' => 'File not found in storage.',
        'upload_failed' => 'Failed to upload file. Please try again.',
        'storage_failed' => 'Failed to save file to storage.',
        'database_failed' => 'Failed to save file information to database.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Success Messages
    |--------------------------------------------------------------------------
    |
    | Success messages for different operations.
    |
    */
    'success_messages' => [
        'uploaded' => 'File uploaded successfully.',
        'updated' => 'File updated successfully.',
        'deleted' => 'File deleted successfully.',
        'replaced' => 'File replaced successfully.',
    ],
];
