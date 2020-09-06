<?php

return [
    'enabled' => env('CRONBOARD_ENABLED', true),

    'discovery' => [
        'paths' => [
            app_path(),
        ],

        'ignore' => [
        ],

        'commands' => [
            /**
             * If set to true Cronboard will record all commands added by third party packages
             */
            'include_third_party' => false,

            /**
             * If third party command recording is enabled we can exclude specific namespaces
             */
            'exclude_namespaces' => [
                'Illuminate\\'
            ],

            /**
             * If third party command recording is enabled we can restrict only to certain namespaces
             */
            'restrict_to_namespaces' => [
                
            ]
        ]
    ],

    'client' => [
        'token' => env('CRONBOARD_TOKEN'),
    ],

    'errors' => [
        // if `true` errors will be caught in order to prevent any
        // disruptions in the cron schedule
        'silent' => true,

        // forward exception to application's error handler
        'report' => false,
    ]
];
