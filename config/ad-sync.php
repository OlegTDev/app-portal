<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AD Sync Frequency
    |--------------------------------------------------------------------------
    |
    | This option controls how often the AD sync command runs.
    | Options: 'hourly', 'daily', or a cron expression like "0 2 * * *"
    |
    */

    'frequency' => env('AD_SYNC_FREQUENCY', 'daily'),

    /*
    |--------------------------------------------------------------------------
    | LDAP Enabled
    |--------------------------------------------------------------------------
    |
    | When true, uses real LDAP service. When false, uses FakeLdapService
    | with hardcoded test users for local development.
    |
    */

    'enabled' => env('LDAP_ENABLED', false),

];
