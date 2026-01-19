<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for AI-powered report analysis and generation features.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | OpenAI Model Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which OpenAI model to use for different operations.
    | Primary: gpt-4o-mini (cost-effective, fast)
    | Fallback: gpt-4o (for complex analysis)
    |
    */

    'openai' => [
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 4000),
        'temperature' => env('OPENAI_TEMPERATURE', 0.3),
        'timeout' => env('OPENAI_REQUEST_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Report Analysis Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for report analysis operations.
    |
    */

    'analysis' => [
        'cache_duration' => env('AI_ANALYSIS_CACHE_DURATION', 2592000), // 30 days in seconds
        'enable_caching' => env('AI_ENABLE_CACHING', true),
        'retry_attempts' => env('AI_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('AI_RETRY_DELAY', 2), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Usage Limits
    |--------------------------------------------------------------------------
    |
    | Approximate token limits for different operations.
    |
    */

    'token_limits' => [
        'monthly_analysis' => 2000,
        'quarterly_generation' => 3000,
        'half_yearly_generation' => 4000,
        'annual_generation' => 6000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable AI features.
    |
    */

    'features' => [
        'enable_ai_analysis' => env('AI_ENABLE_ANALYSIS', true),
        'enable_ai_generation' => env('AI_ENABLE_GENERATION', true),
        'enable_ai_comparison' => env('AI_ENABLE_COMPARISON', true),
        'enable_ai_recommendations' => env('AI_ENABLE_RECOMMENDATIONS', true),
    ],
];
