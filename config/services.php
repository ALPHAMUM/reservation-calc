<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'balesin' => [
        'list_url'   => env('BALESIN_API_LIST_URL'),
        'detail_url' => env('BALESIN_API_DETAIL_URL'),
        'sanity_url' => env('BALESIN_API_SANITY_URL'),
        'api_key'    => env('BALESIN_API_KEY'),
    ],

    'balesin_city' => [
        'list_url'   => env('BALESIN_CITY_API_LIST_URL'),
        'detail_url' => env('BALESIN_CITY_API_DETAIL_URL'),
        'sanity_url' => env('BALESIN_CITY_API_SANITY_URL'),
        'api_key'    => env('BALESIN_CITY_API_KEY'),
    ],

    'balesin_pines' => [
        'list_url'   => env('BALESIN_PINES_API_LIST_URL'),
        'detail_url' => env('BALESIN_PINES_API_DETAIL_URL'),
        'sanity_url' => env('BALESIN_PINES_API_SANITY_URL'),
        'api_key'    => env('BALESIN_PINES_API_KEY'),
    ],

];
