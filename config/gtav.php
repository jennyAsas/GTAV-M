<?php

return [
    // Read through config() so values survive `php artisan config:cache`.
    'admin' => [
        'name' => env('ADMIN_NAME', 'Global Administrator'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],
];
