<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Number provider drivers
    |--------------------------------------------------------------------------
    |
    | Provider credentials belong in environment variables or a secret
    | manager. Never commit provider API keys to the repository.
    |
    */
    'default' => env('NUMBER_PROVIDER_DEFAULT', 'inventory'),

    'drivers' => [
        'inventory' => [
            'driver' => 'inventory',
        ],
    ],
];
