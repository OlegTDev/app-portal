<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default LDAP Connection Name
    |--------------------------------------------------------------------------
    */

    'default' => env('LDAP_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | LDAP Connections
    |--------------------------------------------------------------------------
    */

    'connections' => [

        'default' => [
            'hosts' => [env('LDAP_HOST', '127.0.0.1')],
            'username' => env('LDAP_USERNAME', 'cn=svc-portal,ou=serviceaccounts,dc=domain,dc=local'),
            'password' => env('LDAP_PASSWORD', ''),
            'port' => env('LDAP_PORT', 389),
            'base_dn' => env('LDAP_BASE_DN', 'dc=domain,dc=local'),
            'timeout' => env('LDAP_TIMEOUT', 5),
            'use_tls' => env('LDAP_TLS', false),
            'use_starttls' => env('LDAP_STARTTLS', false),
            'use_sasl' => env('LDAP_SASL', false),
            'sasl_options' => [
                // 'mech' => 'GSSAPI',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | LDAP Logging
    |--------------------------------------------------------------------------
    */

    'logging' => [
        'enabled' => env('LDAP_LOGGING', true),
        'channel' => env('LOG_CHANNEL', 'stack'),
        'level' => env('LOG_LEVEL', 'info'),
    ],

    /*
    |--------------------------------------------------------------------------
    | LDAP Cache
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'enabled' => env('LDAP_CACHE', false),
        'driver' => env('CACHE_DRIVER', 'file'),
    ],

];
