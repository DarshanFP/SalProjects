<?php

return [
    'optimization' => [
        'enabled' => env('REPORT_PHOTO_OPTIMIZATION', true),
        'max_dimension' => (int) env('REPORT_PHOTO_MAX_DIMENSION', 1920),
        'jpeg_quality' => (int) env('REPORT_PHOTO_JPEG_QUALITY', 82),
        'max_file_size_kb' => (int) env('REPORT_PHOTO_MAX_FILE_SIZE_KB', 350),
        'strip_profile' => filter_var(env('REPORT_PHOTO_STRIP_PROFILE', true), FILTER_VALIDATE_BOOLEAN),
        'output_format' => env('REPORT_PHOTO_OUTPUT_FORMAT', 'jpeg'),
    ],
    'fallback_to_original_on_error' => filter_var(env('REPORT_PHOTO_FALLBACK_TO_ORIGINAL', true), FILTER_VALIDATE_BOOLEAN),
];
