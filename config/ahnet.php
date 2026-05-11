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
    | Tenant Billing (SaaS subscription) — superadmin nagih tenant
    |--------------------------------------------------------------------------
    */
    'tenant_billing' => [
        // Hari grace period sebelum auto-suspend setelah due_date lewat
        'grace_days' => (int) env('TENANT_BILLING_GRACE_DAYS', 7),
        // Tanggal generate invoice baru (1 = tanggal 1 tiap bulan)
        'cycle_day'  => (int) env('TENANT_BILLING_CYCLE_DAY', 1),
        // Format invoice number: SUB-YYYY-MM-XXXX
        'invoice_prefix' => env('TENANT_BILLING_INVOICE_PREFIX', 'SUB'),
        // Reminder schedule (hari dari due_date, negatif=sebelum, positif=sesudah)
        'reminder_days'  => [-3, -1, 1, 7],
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

    /*
    |--------------------------------------------------------------------------
    | Billing
    |--------------------------------------------------------------------------
    */
    'billing' => [
        // Day of month invoices are due (1-31). Default = 7.
        'due_day'  => (int) env('BILLING_DUE_DAY', 7),
        // Currency code shown in UI / PDF.
        'currency' => env('BILLING_CURRENCY', 'IDR'),
    ],
];
