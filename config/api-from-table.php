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
        'secret',
        'private_key',
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
    | Treat tinyint(1) as boolean
    |--------------------------------------------------------------------------
    */
    'tinyint_one_as_boolean' => true,

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
    ],
];
