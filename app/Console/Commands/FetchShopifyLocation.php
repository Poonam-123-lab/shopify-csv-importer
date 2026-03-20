<?php

namespace App\Console\Commands;

use App\Services\ShopifyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchShopifyLocation extends Command
{
    protected $signature   = 'shopify:location';
    protected $description = 'Fetch the primary Shopify location GID and display it for .env setup';

    public function handle(): int
    {
        $domain      = config('shopify.shop_domain');
        $token       = config('shopify.access_token');
        $apiVersion  = config('shopify.api_version', '2024-01');

        if (empty($domain) || empty($token)) {
            $this->error('SHOPIFY_SHOP_DOMAIN and SHOPIFY_ACCESS_TOKEN must be set in .env first.');
            return self::FAILURE;
        }

        $url   = "https://{$domain}/admin/api/{$apiVersion}/graphql.json";
        $query = <<<'GQL'
        {
            locations(first: 5) {
                edges {
                    node {
                        id
                        name
                        isActive
                    }
                }
            }
        }
        GQL;

        $this->info("Fetching locations from {$domain}...");

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
            'Content-Type'           => 'application/json',
        ])->post($url, ['query' => $query]);

        if ($response->failed()) {
            $this->error('HTTP Error: ' . $response->status() . ' — ' . $response->body());
            return self::FAILURE;
        }

        $locations = $response->json('data.locations.edges', []);

        if (empty($locations)) {
            $this->error('No locations found. Check your API token permissions.');
            return self::FAILURE;
        }

        $this->table(['ID (GID)', 'Name', 'Active'], array_map(fn($edge) => [
            $edge['node']['id'],
            $edge['node']['name'],
            $edge['node']['isActive'] ? 'Yes' : 'No',
        ], $locations));

        $primaryGid = $locations[0]['node']['id'];

        $this->info("\nAdd this to your .env file:");
        $this->line("SHOPIFY_LOCATION_GID={$primaryGid}");

        return self::SUCCESS;
    }
}
