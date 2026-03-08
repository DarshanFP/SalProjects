<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dashboard Cache TTL
    |--------------------------------------------------------------------------
    |
    | Duration in minutes for dashboard cache. Cached widget data is served on
    | repeated requests within this window. Real-time approval data (pending
    | projects/reports) and activity feed are always recomputed.
    |
    | Used by: Provincial dashboard, Coordinator dashboard (Phase 7)
    |
    */
    'cache_ttl_minutes' => 5,

    /*
    |--------------------------------------------------------------------------
    | Coordinator Dashboard Cache TTL (Phase 7)
    |--------------------------------------------------------------------------
    |
    | Duration in minutes for the full coordinator dashboard payload cache.
    | Overrides cache_ttl_minutes for coordinator dashboard when set.
    | Real-time widgets (pending approvals, activity feed) are never cached.
    |
    */
    'coordinator_dashboard_cache_ttl_minutes' => 10,
];
