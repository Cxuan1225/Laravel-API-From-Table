<?php

return [
    'namespace' => [
        'models' => 'App\\Models',
        'requests' => 'App\\Http\\Requests',
        'data' => 'App\\Data',
        'actions' => 'App\\Actions',
        'resources' => 'App\\Http\\Resources',
        'controllers' => 'App\\Http\\Controllers',
    ],

    'paths' => [
        'models' => app_path('Models'),
        'requests' => app_path('Http/Requests'),
        'data' => app_path('Data'),
        'actions' => app_path('Actions'),
        'resources' => app_path('Http/Resources'),
        'controllers' => app_path('Http/Controllers'),
    ],

    'exclude_fillable' => [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
    ],

    'sensitive_columns' => [
        'password',
        'remember_token',
        'api_token',
        'secret',
        'private_key',
        'two_factor_secret',
    ],

    'sensitive_patterns' => [
        'password',
        'secret',
    ],

    'tinyint_one_as_boolean' => true,

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
