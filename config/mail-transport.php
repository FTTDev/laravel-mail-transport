<?php

return [

    /*
    | When null, the package uses config('mail.default') / MAIL_MAILER.
    | Set to "microsoft_graph" to force Graph when using env-only setup.
    */
    'default_mailer' => env('MAIL_TRANSPORT_DEFAULT'),

    'microsoft_graph' => [
        'client_id' => env('MAIL_GRAPH_CLIENT_ID'),
        'client_secret' => env('MAIL_GRAPH_CLIENT_SECRET'),
        'tenant_id' => env('MAIL_GRAPH_TENANT_ID'),
        'graph_endpoint' => env('MAIL_GRAPH_GRAPH_ENDPOINT'),
        'auth_endpoint' => env('MAIL_GRAPH_AUTH_ENDPOINT'),
        'no_save' => env('MAIL_GRAPH_NO_SAVE', false),
    ],
];
