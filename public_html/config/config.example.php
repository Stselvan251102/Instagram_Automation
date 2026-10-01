<?php
/**
 * Example PHP Configuration File
 * Copy this file to config.php or populate .env file
 */

return [
    'app' => [
        'name' => 'Carouselfy',
        'env'  => 'production',
        'url'  => 'https://yourdomain.com',
        'secret' => 'YOUR_RANDOM_SECRET_KEY',
    ],
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'u123456_carouselfy',
        'user' => 'u123456_admin',
        'password' => 'YOUR_DB_PASSWORD',
    ],
    'ai' => [
        'groq_api_key' => 'gsk_YOUR_GROQ_API_KEY',
        'groq_model' => 'openai/gpt-oss-120b',
        'openai_api_key' => 'sk-proj-YOUR_OPENAI_API_KEY',
        'openai_model' => 'gpt-4o-mini',
        'gemini_api_key' => 'AIzaSy_YOUR_GEMINI_API_KEY',
        'openrouter_api_key' => '',
    ],
    'instagram' => [
        'app_id' => 'YOUR_META_APP_ID',
        'app_secret' => 'YOUR_META_APP_SECRET',
        'redirect_uri' => 'https://yourdomain.com/api/instagram/callback.php',
        'graph_version' => 'v20.0',
    ],
    'cron' => [
        'secret' => 'YOUR_CRON_SECRET_TOKEN',
    ]
];
