<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Impersonation (Phase 4+)
    |--------------------------------------------------------------------------
    | When true, admin can see impersonation UI entry points. Backend logic
    | is implemented separately; this flag only controls UI visibility.
    | Default: false. Do not enable without business approval.
    */
    'impersonation_enabled' => env('ADMIN_IMPERSONATION_ENABLED', false),
];
