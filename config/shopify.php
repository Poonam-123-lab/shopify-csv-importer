<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shopify Configuration
    |--------------------------------------------------------------------------
    | Set these in your .env file:
    |
    |   SHOPIFY_SHOP_DOMAIN=your-store.myshopify.com
    |   SHOPIFY_ACCESS_TOKEN=shpat_xxxxxxxxxxxxxxxxxxxx
    |   SHOPIFY_API_VERSION=2024-01
    |   SHOPIFY_LOCATION_GID=gid://shopify/Location/YOUR_LOCATION_ID
    |   SHOPIFY_DEFAULT_COLLECTION_ID=123456789
    |
    | DEMO MODE: If SHOPIFY_SHOP_DOMAIN or SHOPIFY_ACCESS_TOKEN are empty,
    | the app runs in demo mode — API calls are simulated without making
    | real requests. Set credentials to enable live Shopify sync.
    */

    'shop_domain'           => env('SHOPIFY_SHOP_DOMAIN', ''),
    'access_token'          => env('SHOPIFY_ACCESS_TOKEN', ''),
    'api_version'           => env('SHOPIFY_API_VERSION', '2024-01'),
    'location_gid'          => env('SHOPIFY_LOCATION_GID', 'gid://shopify/Location/1'),
    'default_collection_id' => env('SHOPIFY_DEFAULT_COLLECTION_ID', ''),
];