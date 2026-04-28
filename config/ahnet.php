<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Brand
    |--------------------------------------------------------------------------
    */
    'brand'   => env('APP_NAME', 'AHNet ISP Dashboard'),
    'company' => env('AHNET_COMPANY', 'PT. AHNet'),

    /*
    |--------------------------------------------------------------------------
    | RADIUS / PPPoE / Hotspot defaults
    |--------------------------------------------------------------------------
    */
    'radius' => [
        'connection' => 'radius',
        'default_pppoe_group'   => env('RADIUS_DEFAULT_PPPOE_GROUP', 'pppoe-default'),
        'default_hotspot_group' => env('RADIUS_DEFAULT_HOTSPOT_GROUP', 'hotspot-default'),
        'pppoe_username_prefix' => env('PPPOE_USERNAME_PREFIX', 'ahnet_'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mikrotik default device
    |--------------------------------------------------------------------------
    */
    'mikrotik' => [
        'host'     => env('MIKROTIK_HOST', '192.168.88.1'),
        'user'     => env('MIKROTIK_USER', 'admin'),
        'password' => env('MIKROTIK_PASSWORD', ''),
        'port'     => (int) env('MIKROTIK_PORT', 8728),
        'ssl'      => filter_var(env('MIKROTIK_USE_SSL', false), FILTER_VALIDATE_BOOLEAN),
        'timeout'  => (int) env('MIKROTIK_TIMEOUT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | SNMP defaults
    |--------------------------------------------------------------------------
    */
    'snmp' => [
        'community' => env('SNMP_COMMUNITY', 'public'),
        'version'   => env('SNMP_VERSION', '2c'),
        'timeout'   => (int) env('SNMP_TIMEOUT', 1_000_000),
        'retries'   => (int) env('SNMP_RETRIES', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | GenieACS NBI
    |--------------------------------------------------------------------------
    */
    'genieacs' => [
        'nbi_url'  => env('GENIEACS_NBI_URL', 'http://127.0.0.1:7557'),
        'username' => env('GENIEACS_USERNAME', ''),
        'password' => env('GENIEACS_PASSWORD', ''),
        'timeout'  => (int) env('GENIEACS_TIMEOUT', 10),
    ],
];
