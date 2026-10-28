<?php

$list = fn (string $key, string $default = '') => array_values(array_filter(array_map(
    fn ($v) => strtolower(trim($v)),
    explode(',', (string) env($key, $default))
)));

return [
    // Microsoft tenant (directory) ID of MCC. Leave empty to skip the tenant check.
    'tenant_id' => env('AZURE_TENANT_ID') && ! in_array(env('AZURE_TENANT_ID'), ['common', 'organizations', 'consumers'], true)
        ? env('AZURE_TENANT_ID')
        : null,

    'allowed_domains' => $list('MCC_ALLOWED_DOMAINS', 'mcc.edu.ph'),

    // These accounts get the admin role when they sign in
    'admin_emails' => $list('MCC_ADMIN_EMAILS'),

    // A technician with this many open work orders is shown as "Busy"
    'busy_threshold' => (int) env('MCC_BUSY_THRESHOLD', 7),
];
