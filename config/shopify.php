<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shopify Store Configuration
    |--------------------------------------------------------------------------
    | Configure your Shopify store credentials in .env:
    |   SHOPIFY_SHOP_DOMAIN=your-store.myshopify.com
    |   SHOPIFY_ACCESS_TOKEN=shpat_xxxxxxxxxxxx
    |   SHOPIFY_API_VERSION=2024-01
    |
    | If credentials are not set, the app runs in DEMO mode and simulates
    | Shopify API calls without making real requests.
    */

    'shop_domain'  => env('SHOPIFY_SHOP_DOMAIN', ''),
    'access_token' => env('SHOPIFY_ACCESS_TOKEN', ''),
    'api_version'  => env('SHOPIFY_API_VERSION', '2024-01'),
];