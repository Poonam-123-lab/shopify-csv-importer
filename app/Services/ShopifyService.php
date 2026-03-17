<?php

namespace App\Services;

use App\Models\ErrorLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyService
{
    private string $shopDomain;
    private string $apiVersion;
    private string $accessToken;
    private string $graphqlUrl;
    private string $restBaseUrl;
    private bool   $isDemoMode;

    public function __construct()
    {
        $this->shopDomain  = config('shopify.shop_domain', '');
        $this->apiVersion  = config('shopify.api_version', '2024-01');
        $this->accessToken = config('shopify.access_token', '');
        $this->graphqlUrl  = "https://{$this->shopDomain}/admin/api/{$this->apiVersion}/graphql.json";
        $this->restBaseUrl = "https://{$this->shopDomain}/admin/api/{$this->apiVersion}";
        $this->isDemoMode  = empty($this->shopDomain) || empty($this->accessToken);
    }

    // -------------------------------------------------------------------------
    // PUBLIC: Create or Update Product (upsert by SKU → title fallback)
    // -------------------------------------------------------------------------

    /**
     * Create or update a product in Shopify.
     * Returns ['shopify_product_id' => string, 'action' => 'created'|'updated']
     */
    public function upsertProduct(array $productData, ?string $collectionId = null): array
    {
        if ($this->isDemoMode) {
            return $this->demoUpsert($productData);
        }

        // 1. Try to find existing product by SKU then title
        $existing = $this->findExistingProduct(
            $productData['sku']   ?? null,
            $productData['title'] ?? null
        );

        if ($existing) {
            $result = $this->updateProductGraphQL($existing['id'], $productData);
            $result['action'] = 'updated';

            // Assign to collection if provided
            if ($collectionId) {
                $this->addProductToCollection($result['shopify_product_id'], $collectionId);
            }

            Log::info('Shopify: product updated', [
                'title'              => $productData['title'],
                'shopify_product_id' => $result['shopify_product_id'],
            ]);

            return $result;
        }

        // 2. Create new product
        $result = $this->createProductGraphQL($productData);
        $result['action'] = 'created';

        // Assign to collection if provided
        if ($collectionId) {
            $this->addProductToCollection($result['shopify_product_id'], $collectionId);
        }

        Log::info('Shopify: product created', [
            'title'              => $productData['title'],
            'shopify_product_id' => $result['shopify_product_id'],
        ]);

        return $result;
    }

    // -------------------------------------------------------------------------
    // GRAPHQL: Create Product
    // -------------------------------------------------------------------------

    private function createProductGraphQL(array $data): array
    {
        $mutation = <<<'GQL'
        mutation productCreate($input: ProductInput!) {
            productCreate(input: $input) {
                product {
                    id
                    title
                    variants(first: 1) {
                        edges {
                            node {
                                id
                                sku
                                price
                            }
                        }
                    }
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        $variables = [
            'input' => $this->buildProductInput($data),
        ];

        $response = $this->graphqlRequest($mutation, $variables);

        $errors = $response['data']['productCreate']['userErrors'] ?? [];
        if (!empty($errors)) {
            $msg = implode('; ', array_column($errors, 'message'));
            throw new \Exception('Shopify GraphQL userErrors: ' . $msg);
        }

        $product = $response['data']['productCreate']['product'];
        return [
            'shopify_product_id' => $this->extractNumericId($product['id']),
            'shopify_gid'        => $product['id'],
        ];
    }

    // -------------------------------------------------------------------------
    // GRAPHQL: Update Product
    // -------------------------------------------------------------------------

    private function updateProductGraphQL(string $gid, array $data): array
    {
        $mutation = <<<'GQL'
        mutation productUpdate($input: ProductInput!) {
            productUpdate(input: $input) {
                product {
                    id
                    title
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        $input         = $this->buildProductInput($data);
        $input['id']   = $gid;

        $response = $this->graphqlRequest($mutation, ['input' => $input]);

        $errors = $response['data']['productUpdate']['userErrors'] ?? [];
        if (!empty($errors)) {
            $msg = implode('; ', array_column($errors, 'message'));
            throw new \Exception('Shopify GraphQL update userErrors: ' . $msg);
        }

        $product = $response['data']['productUpdate']['product'];
        return [
            'shopify_product_id' => $this->extractNumericId($product['id']),
            'shopify_gid'        => $product['id'],
        ];
    }

    // -------------------------------------------------------------------------
    // GRAPHQL: Find existing product by SKU or title
    // -------------------------------------------------------------------------

    public function findExistingProduct(?string $sku, ?string $title): ?array
    {
        if ($this->isDemoMode) {
            return null;
        }

        // Try SKU first (more reliable)
        if ($sku) {
            $result = $this->searchProductBySku($sku);
            if ($result) {
                return $result;
            }
        }

        // Fall back to title search
        if ($title) {
            return $this->searchProductByTitle($title);
        }

        return null;
    }

    private function searchProductBySku(string $sku): ?array
    {
        $query = <<<'GQL'
        query searchBySku($query: String!) {
            products(first: 1, query: $query) {
                edges {
                    node {
                        id
                        title
                        variants(first: 1) {
                            edges {
                                node { sku }
                            }
                        }
                    }
                }
            }
        }
        GQL;

        $response = $this->graphqlRequest($query, ['query' => 'sku:' . $sku]);
        $edges    = $response['data']['products']['edges'] ?? [];

        if (empty($edges)) {
            return null;
        }

        $node    = $edges[0]['node'];
        $varSku  = $node['variants']['edges'][0]['node']['sku'] ?? '';

        // Exact SKU match check
        if (strtolower($varSku) !== strtolower($sku)) {
            return null;
        }

        return ['id' => $node['id'], 'title' => $node['title']];
    }

    private function searchProductByTitle(string $title): ?array
    {
        $query = <<<'GQL'
        query searchByTitle($query: String!) {
            products(first: 1, query: $query) {
                edges {
                    node {
                        id
                        title
                    }
                }
            }
        }
        GQL;

        $safeTitle = addslashes($title);
        $response  = $this->graphqlRequest($query, ['query' => 'title:"' . $safeTitle . '"']);
        $edges     = $response['data']['products']['edges'] ?? [];

        if (empty($edges)) {
            return null;
        }

        $node = $edges[0]['node'];

        // Exact title match
        if (strtolower($node['title']) !== strtolower($title)) {
            return null;
        }

        return ['id' => $node['id'], 'title' => $node['title']];
    }

    // -------------------------------------------------------------------------
    // GRAPHQL: Add product to collection
    // -------------------------------------------------------------------------

    public function addProductToCollection(string $productId, string $collectionId): void
    {
        if ($this->isDemoMode) {
            return;
        }

        // collectionId may be numeric or GID
        $collectionGid = str_starts_with($collectionId, 'gid://')
            ? $collectionId
            : "gid://shopify/Collection/{$collectionId}";

        $productGid = str_starts_with($productId, 'gid://')
            ? $productId
            : "gid://shopify/Product/{$productId}";

        $mutation = <<<'GQL'
        mutation collectionAddProducts($id: ID!, $productIds: [ID!]!) {
            collectionAddProducts(id: $id, productIds: $productIds) {
                collection {
                    id
                    title
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        $response = $this->graphqlRequest($mutation, [
            'id'         => $collectionGid,
            'productIds' => [$productGid],
        ]);

        $errors = $response['data']['collectionAddProducts']['userErrors'] ?? [];
        if (!empty($errors)) {
            $msg = implode('; ', array_column($errors, 'message'));
            Log::warning('Shopify: collection assignment failed', [
                'collection_id' => $collectionId,
                'product_id'    => $productId,
                'errors'        => $msg,
            ]);
        } else {
            Log::info('Shopify: product added to collection', [
                'product_id'    => $productId,
                'collection_id' => $collectionId,
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // GRAPHQL: Delete product
    // -------------------------------------------------------------------------

    public function deleteProduct(string $shopifyProductId): bool
    {
        if ($this->isDemoMode) {
            return true;
        }

        $gid      = "gid://shopify/Product/{$shopifyProductId}";
        $mutation = <<<'GQL'
        mutation productDelete($input: ProductDeleteInput!) {
            productDelete(input: $input) {
                deletedProductId
                userErrors { field message }
            }
        }
        GQL;

        $response = $this->graphqlRequest($mutation, ['input' => ['id' => $gid]]);
        $errors   = $response['data']['productDelete']['userErrors'] ?? [];

        return empty($errors);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function buildProductInput(array $data): array
    {
        $input = [
            'title'       => $data['title'],
            'bodyHtml'    => $data['description'] ?? '',
            'vendor'      => $data['vendor']       ?? '',
            'productType' => $data['product_type'] ?? '',
            'tags'        => $data['tags']          ?? '',
            'variants'    => [[
                'price'             => (string) ($data['price'] ?? '0.00'),
                'sku'               => $data['sku'] ?? '',
                'compareAtPrice'    => isset($data['compare_at_price']) ? (string) $data['compare_at_price'] : null,
                'inventoryQuantities' => [
                    [
                        'availableQuantity' => (int) ($data['inventory_quantity'] ?? 0),
                        'locationId'        => config('shopify.location_gid', 'gid://shopify/Location/1'),
                    ],
                ],
            ]],
        ];

        // Remove null values
        $input['variants'][0] = array_filter($input['variants'][0], fn($v) => $v !== null);

        return $input;
    }

    private function graphqlRequest(string $query, array $variables = []): array
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type'           => 'application/json',
        ])
        ->timeout(30)
        ->post($this->graphqlUrl, [
            'query'     => $query,
            'variables' => $variables,
        ]);

        if ($response->failed()) {
            throw new \Exception('Shopify GraphQL HTTP error ' . $response->status() . ': ' . $response->body());
        }

        $json = $response->json();

        if (!empty($json['errors'])) {
            $msg = implode('; ', array_column($json['errors'], 'message'));
            throw new \Exception('Shopify GraphQL errors: ' . $msg);
        }

        Log::debug('Shopify GraphQL response', [
            'status'    => $response->status(),
            'variables' => $variables,
        ]);

        return $json;
    }

    private function extractNumericId(string $gid): string
    {
        // gid://shopify/Product/12345678 → 12345678
        return (string) last(explode('/', $gid));
    }

    private function demoUpsert(array $data): array
    {
        $fakeId = 'DEMO_' . strtoupper(substr(md5(($data['sku'] ?? '') . ($data['title'] ?? '')), 0, 10));
        Log::info('Shopify DEMO mode — simulating upsert', ['title' => $data['title'] ?? 'unknown']);
        return [
            'shopify_product_id' => $fakeId,
            'shopify_gid'        => 'gid://shopify/Product/' . $fakeId,
            'action'             => 'created',
        ];
    }
}