<?php

return [
    'name' => 'PWA Support',
    'description' => 'Progressive Web App (PWA) support for all themes',
    'settings' => [
        'title' => 'PWA Settings',
        'description' => 'Configure Progressive Web App (PWA) settings',
        'enable' => 'Enable PWA',
        'app_name' => 'App Name',
        'app_name_placeholder' => 'Your application name',
        'short_name' => 'Short Name',
        'short_name_placeholder' => 'Short name for your app',
        'theme_color' => 'Theme Color',
        'background_color' => 'Background Color',
        'icon' => 'App Icon',
        'icon_description' => 'This icon will be used for PWA. Recommended size: 512x512px',
        'start_url' => 'Start URL',
        'display' => 'Display Mode',
        'orientation' => 'Orientation',
        'save_settings' => 'Save Settings',
        'display_options' => [
            'fullscreen' => 'Fullscreen',
            'standalone' => 'Standalone',
            'minimal_ui' => 'Minimal UI',
            'browser' => 'Browser',
        ],
        'orientation_options' => [
            'any' => 'Any',
            'natural' => 'Natural',
            'landscape' => 'Landscape',
            'portrait' => 'Portrait',
        ],
    ],
];
