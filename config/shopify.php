<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shopify Store Domain
    |--------------------------------------------------------------------------
    | Example: my-store.myshopify.com  (no https://, no trailing slash)
    */
    'shop_domain' => env('SHOPIFY_STORE_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Admin API Access Token
    |--------------------------------------------------------------------------
    */
    'access_token' => env('SHOPIFY_ACCESS_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Admin API Version
    |--------------------------------------------------------------------------
    */
    'api_version' => env('SHOPIFY_API_VERSION', '2024-01'),

    /*
    |--------------------------------------------------------------------------
    | Default Collection ID
    |--------------------------------------------------------------------------
    | Numeric ID or full GID. Used when no collection_id is passed at upload time.
    */
    'collection_id' => env('SHOPIFY_COLLECTION_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Shopify Location GID
    |--------------------------------------------------------------------------
    | Required for inventory tracking on product variants.
    | Run: php artisan shopify:location  (see below) to auto-fetch and save it.
    | Or paste manually: gid://shopify/Location/XXXXXXXXXX
    */
    'location_gid' => env('SHOPIFY_LOCATION_GID', ''),

];
