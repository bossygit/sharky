<?php

declare(strict_types=1);

use function Illuminate\Filesystem\Filesystem;

return [

    /*
    |--------------------------------------------------------------------------
    | LLM default provider
    |--------------------------------------------------------------------------
    |
    | Values: deepseek, nvidia
    |
    */

    'llm' => [
        'provider' => env('LLM_PROVIDER', 'deepseek'),

        'deepseek' => [
            'api_key' => env('DEEPSEEK_API_KEY'),
            'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com/v1'),
            'model' => env('DEEPSEEK_MODEL_CHAT', 'deepseek-chat'),
            'timeout' => (int) env('DEEPSEEK_TIMEOUT', 60),
            'max_retries' => (int) env('DEEPSEEK_MAX_RETRIES', 5),
        ],

        'nvidia' => [
            'api_key' => env('NVIDIA_API_KEY'),
            'base_url' => env('NVIDIA_BASE_URL', 'https://integrate.api.nvidia.com/v1'),
            'model' => env('NVIDIA_MODEL_CHAT', 'google/gemma-4-31b-it'),
            'timeout' => (int) env('NVIDIA_TIMEOUT', 60),
            'max_retries' => (int) env('NVIDIA_MAX_RETRIES', 5),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Web search
    |--------------------------------------------------------------------------
    */

    'websearch' => [
        'provider' => env('WEBSEARCH_PROVIDER', 'brave'),
        'brave' => [
            'api_key' => env('BRAVE_SEARCH_API_KEY'),
            'base_url' => env('BRAVE_SEARCH_BASE_URL', 'https://api.search.brave.com/res/v1/web/search'),
            'timeout' => (int) env('BRAVE_SEARCH_TIMEOUT', 20),
        ],
        'max_sources' => (int) env('ENRICHMENT_MAX_SOURCES', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */

    'fields' => [
        'description_html',
        'features',
        'faqs',
        'meta_title',
        'meta_description',
    ],

];
