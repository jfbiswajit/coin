<?php

return [
    'install-button' => true,

    'manifest' => [
        'name' => 'Coin',
        'short_name' => 'Coin',
        'background_color' => '#0F0F1A',
        'display' => 'standalone',
        'description' => 'Personal finance tracking — budget, income, and expenses.',
        'theme_color' => '#7C3AED',
        'start_url' => '/dashboard',
        'icons' => [
            [
                'src' => 'icons/icon-192.png',
                'sizes' => '192x192',
                'type' => 'image/png',
            ],
            [
                'src' => 'icons/icon-512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
            ],
        ],
    ],

    'debug' => env('APP_DEBUG', false),
    'livewire-app' => false,
];
