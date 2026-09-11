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
    'drivers' => [
        'inventory' => [
            'driver' => 'inventory',
        ],
    ],
];
