<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Generated Namespaces
    |--------------------------------------------------------------------------
    */
    'namespace' => [
        'models' => 'App\\Models',
        'requests' => 'App\\Http\\Requests',
        'data' => 'App\\Data',
        'actions' => 'App\\Actions',
        'resources' => 'App\\Http\\Resources',
        'controllers' => 'App\\Http\\Controllers',
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Paths
    |--------------------------------------------------------------------------
    */
    'paths' => [
        'models' => app_path('Models'),
        'requests' => app_path('Http/Requests'),
        'data' => app_path('Data'),
        'actions' => app_path('Actions'),
        'resources' => app_path('Http/Resources'),
        'controllers' => app_path('Http/Controllers'),
        'routes' => base_path('routes/web.php'),
        'api_routes' => base_path('routes/api.php'),
        'tests' => base_path('tests/Feature'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Columns Excluded From $fillable & Validation
    |--------------------------------------------------------------------------
    */
    'exclude_fillable' => [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Columns (Exact Match)
    |--------------------------------------------------------------------------
    | Column names that exactly equal any value here are excluded from
    | $fillable and validation rules.
    */
    'sensitive_columns' => [
        'password',
        'remember_token',
        'api_token',
        'refresh_token',
        'access_token',
        'token',
        'otp',
        'otp_secret',
        'api_key',
        'client_secret',
        'secret',
        'private_key',
        'recovery_codes',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Patterns (Substring Match, Case-Insensitive)
    |--------------------------------------------------------------------------
    | Column names that CONTAIN any pattern here are excluded. Catches naming
    | variants like 'user_password', 'old_password', 'client_secret', etc.
    */
    'sensitive_patterns' => [
        'password',
        'secret',
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Exposure Policies
    |--------------------------------------------------------------------------
    |
    | Policy-specific explicit includes. Defaults keep password writable while
    | hiding it from model arrays and resources.
    */
    'field_policies' => [
        'fillable' => [],
        'request_rules' => [],
        'resource_visible' => [],
        'model_hidden' => [],
        'write_only' => [
            'password',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Treat tinyint(1) as boolean
    |--------------------------------------------------------------------------
    */
    'tinyint_one_as_boolean' => true,

    /*
    |--------------------------------------------------------------------------
    | Generated Route Naming
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'resource_name_style' => 'kebab',
        'prefixes' => [
            'routes' => '',
            'api_routes' => 'api',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Which Files To Generate By Default
    |--------------------------------------------------------------------------
    */
    'generate' => [
        'model' => true,
        'store_request' => true,
        'update_request' => true,
        'dto' => true,
        'actions' => true,
        'resource' => true,
        'controller' => true,
        'relationships' => false,
    ],
];
