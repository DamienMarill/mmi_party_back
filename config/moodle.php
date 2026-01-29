<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Moodle OAuth Configuration
    |--------------------------------------------------------------------------
    */

    'url' => env('MOODLE_URL'),
    'client_id' => env('MOODLE_OAUTH_CLIENT_ID'),
    'client_secret' => env('MOODLE_OAUTH_CLIENT_SECRET'),
    'redirect_uri' => env('APP_URL') . '/api/auth/moodle/callback',

    /*
    |--------------------------------------------------------------------------
    | Frontend Redirect URLs
    |--------------------------------------------------------------------------
    */

    'frontend_success_url' => env('APP_FRONT_URL') . '/auth/moodle/success',
    'frontend_error_url' => env('APP_FRONT_URL') . '/auth/moodle/error',
    'frontend_register_url' => env('APP_FRONT_URL') . '/register/finalize',

    /*
    |--------------------------------------------------------------------------
    | Cohort to Group Mapping
    |--------------------------------------------------------------------------
    | Maps Moodle cohort names to application user groups.
    | Order matters: first match wins (more specific first).
    */

    'cohort_mapping' => [
        'staff' => ['Enseignants MMI'],
        'mmi3' => ['MMI3'],
        'mmi2' => ['MMI2'],
        'mmi1' => ['MMI1'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Access Validation Cohorts
    |--------------------------------------------------------------------------
    | User must be in at least one of these cohorts to access the app.
    */

    'required_cohorts' => ['MMI', 'MMI1', 'MMI2', 'MMI3', 'Enseignants MMI'],
];
