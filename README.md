# Shopify CSV Product Import System

A **Laravel 12** application that allows admin users to upload CSV files containing product data, process them **asynchronously via Laravel Queues**, import products to **Shopify using the GraphQL Admin API**, and track every product's import status through a full-featured admin dashboard.

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Setup Instructions](#setup-instructions)
- [Demo Credentials](#demo-credentials)
- [Implementation Overview](#implementation-overview)
- [Database Schema](#database-schema)
- [Design Decisions & Assumptions](#design-decisions--assumptions)
- [Testing the Application](#testing-the-application)
- [Bonus Features](#bonus-features)
- [Project Structure](#project-structure)

---

## Features

- CSV upload with **drag & drop**, client-side file type and size validation
- **Asynchronous background processing** — jobs dispatched to Laravel Queue (database driver)
- **Shopify GraphQL Admin API** — `productCreate`, `productUpdate`, `collectionAddProducts` mutations
- **Upsert logic** — products matched by SKU first, title as fallback; existing products are updated, never duplicated
- Products automatically assigned to a **Shopify Collection** after import
- **Live progress bar** on the upload detail page — polls status every 3 seconds, no page reload needed
- Per-product status tracking: `pending → processing → synced / failed`
- **Retry failed products** individually from the UI
- **Exponential backoff** on Shopify sync failures (10s → 30s → 1m → 2m → 5m, up to 5 attempts)
- **Error Logs** — captures validation errors, Shopify API errors, system errors with row numbers and raw data
- **Activity Logs** — full audit trail of every import event with level/event filtering
- Session-based admin authentication with multiple demo accounts
- Demo mode — if Shopify credentials are missing, API calls are simulated so the UI can be tested locally

---

## Tech Stack

| Layer          | Choice                                           |
|----------------|--------------------------------------------------|
| Framework      | Laravel 12 (PHP 8.2)                             |
| Database       | MySQL                                            |
| Queue Driver   | Database (`jobs` table)                          |
| Shopify API    | GraphQL Admin API — version `2024-01`            |
| HTTP Client    | Laravel HTTP (Guzzle)                            |
| Frontend       | Blade Templates + Tailwind CSS CDN + Font Awesome 6 |
| Auth           | Custom session-based admin authentication        |
| Dev Server     | XAMPP / `php artisan serve`                      |

---

## Setup Instructions

### Prerequisites

- PHP 8.2+
- Composer
- MySQL
- XAMPP or any local server

---

### Step 1 — Clone the repository

```bash
git clone https://github.com/Poonam-123-lab/shopifyTask.git
cd shopifyTask
composer install
```

---

### Step 2 — Create environment file

```bash
cp .env.example .env
php artisan key:generate
```

---

### Step 3 — Configure `.env`

Open `.env` and update the following values:

```dotenv
APP_NAME=Laravel
APP_ENV=local
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=productimportdb
DB_USERNAME=root
DB_PASSWORD=

# Queue — MUST be 'database', NOT 'sync'
QUEUE_CONNECTION=database
SESSION_DRIVER=database

# Shopify
SHOPIFY_SHOP_DOMAIN=laravel-import-test.myshopify.com
SHOPIFY_ACCESS_TOKEN=your_shopify_access_token_here
SHOPIFY_DEFAULT_COLLECTION_ID=464337174767
SHOPIFY_API_VERSION=2024-01
SHOPIFY_LOCATION_GID=           ← fill this in Step 6
```

> **Important:** `QUEUE_CONNECTION` must be set to `database`. If left as `sync`, jobs run synchronously and block the HTTP request — the async requirement will not be met.

---

### Step 4 — Create database and run migrations

Create a MySQL database named `productimportdb`, then run:

```bash
php artisan migrate
```

This creates the following tables:
`users`, `sessions`, `cache`, `jobs`, `failed_jobs`, `uploads`, `products`, `error_logs`, `activity_logs`, `import_batches`, `import_jobs`

---

### Step 5 — Storage symlink

```bash
php artisan storage:link
```

Then clear config cache:

```bash
php artisan config:clear
php artisan config:cache
```

---

### Step 6 — Start the queue worker

Open a **dedicated terminal** and keep it running:

```bash
php artisan queue:work --queue=csv,shopify
```

> This terminal must stay open while testing. Without it, uploaded jobs will stay in `pending` state forever.

---

### Step 7 — Start the development server

In a second terminal:

```bash
php artisan serve
```

Open your browser and visit: **http://localhost:8000/admin/login**

---

## Demo Credentials

| Role     | Email                             | Password     |
|----------|-----------------------------------|--------------|
| Admin    | admin@shopifyimporter.com         | admin123     |


### Key Files

| File | Responsibility |
|------|---------------|
| `app/Jobs/ProcessCsvImport.php` | Parses CSV, validates rows, dispatches per-product sync jobs |
| `app/Jobs/SyncProductToShopify.php` | Syncs one product to Shopify, handles retries |
| `app/Services/ShopifyService.php` | All GraphQL mutations and queries, upsert logic, demo mode |
| `app/Http/Controllers/Admin/UploadController.php` | File upload, listing, show, delete |
| `app/Http/Controllers/Admin/ProductController.php` | Product listing, detail, retry, delete |
| `app/Http/Controllers/Admin/ImportController.php` | Live status JSON endpoint for progress polling |
| `app/Http/Controllers/Admin/DashboardController.php` | KPI stats, recent uploads, activity feed |
| `app/Models/ActivityLog.php` | Audit trail with static `record()` helper |
| `config/shopify.php` | All Shopify configuration values |

### Shopify GraphQL Mutations Used

```graphql
mutation productCreate($input: ProductInput!) { ... }
mutation productUpdate($input: ProductInput!) { ... }
mutation collectionAddProducts($id: ID!, $productIds: [ID!]!) { ... }
query searchBySku($query: String!) { products(first: 1, query: $query) { ... } }
query searchByTitle($query: String!) { products(first: 1, query: $query) { ... } }
```

---

## Database Schema

### `uploads`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| file_name | string | Original filename |
| file_path | string | Storage path |
| status | enum | pending / processing / completed / failed |
| total_rows | int | Total CSV data rows |
| processed_rows | int | Rows dispatched to Shopify |
| failed_rows | int | Rows that failed validation |
| collection_id | string | Shopify collection to assign products |
| created_at | timestamp | Upload time |

### `products`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| upload_id | bigint | FK → uploads |
| title | string | Product title |
| description | text | Body HTML |
| price | decimal | Variant price |
| sku | string | Variant SKU (indexed) |
| vendor | string | Product vendor |
| product_type | string | Product type |
| tags | string | Comma-separated tags |
| compare_at_price | decimal | Original price |
| inventory_quantity | int | Stock quantity |
| shopify_product_id | string | Shopify numeric ID (indexed) |
| shopify_action | string | 'created' or 'updated' |
| status | enum | pending / processing / synced / failed / skipped |

### `error_logs`
| Column | Type | Description |
|--------|------|-------------|
| upload_id | bigint | FK → uploads |
| product_id | bigint | FK → products (nullable) |
| message | text | Error message |
| type | enum | validation / shopify / parsing / system |
| row_number | int | CSV row that caused the error |
| raw_data | text | JSON of the original CSV row |

### `activity_logs`
| Column | Type | Description |
|--------|------|-------------|
| event | string | e.g. file_uploaded, job_dispatched, product_retry |
| level | string | info / warning / error / debug |
| message | text | Human-readable description |
| upload_id | bigint | FK → uploads (nullable) |
| product_id | bigint | FK → products (nullable) |
| context | json | Additional structured data |

---

## Design Decisions & Assumptions

### Design Decisions

**1. Two separate queues (`csv` and `shopify`)**
The CSV parsing job (`ProcessCsvImport`) runs on the `csv` queue and the per-product Shopify sync (`SyncProductToShopify`) runs on the `shopify` queue. This means if Shopify rate limits kick in, the CSV parsing is unaffected and can continue creating product records. They scale independently.

**2. GraphQL over REST**
All Shopify API calls use the GraphQL Admin API (`/admin/api/2024-01/graphql.json`). REST was not used anywhere. This satisfies the bonus requirement and is the recommended approach for Shopify's newer API versions.

**3. Upsert by SKU → Title fallback**
When syncing a product, the system first searches Shopify by exact SKU match. If no SKU is present or no match is found, it falls back to exact title match. This prevents duplicate products when the same CSV is uploaded multiple times.

**4. Demo mode**
If `SHOPIFY_SHOP_DOMAIN` or `SHOPIFY_ACCESS_TOKEN` are empty in `.env`, `ShopifyService` enters demo mode — it simulates API responses and returns fake Shopify IDs. This lets the UI be fully tested without real credentials.

**5. Exponential backoff on sync failures**
`SyncProductToShopify` retries up to 5 times with increasing delays: 10s, 30s, 1m, 2m, 5m. This handles Shopify rate limits and transient network errors gracefully without hammering the API.

**6. Session-based auth (no Breeze/Sanctum)**
Admin authentication uses plain Laravel sessions with hardcoded demo credentials. This keeps the project dependency-light and is appropriate for a demo application. Production would use Laravel Sanctum or proper user management.

**7. Database queue driver (not Redis)**
`QUEUE_CONNECTION=database` was chosen for portability — it works on any MySQL setup without requiring Redis. Switching to Redis/Horizon for production requires only changing the `.env` value.

### Assumptions

- The CSV format follows Shopify's standard product export format (as provided in the sample file). The column mapper handles all standard Shopify column names including aliases like `Body HTML`, `Variant Price`, `Variant SKU`, `Variant Inventory Qty`.
- A single Shopify location is used for inventory. The Location GID is fetched once and stored in `.env` via `SHOPIFY_LOCATION_GID`.
- The collection ID `464337174767` is pre-filled as the default in the upload form but can be overridden per upload.
- Products without a SKU fall back to title-based duplicate detection. If both are missing, a new product is always created.
- The `QUEUE_CONNECTION` must be `database` — jobs will not process asynchronously if left as `sync`.

---

## Testing the Application

### Test 1 — Basic import flow

1. Log in at `http://localhost:8000/admin/login`
2. Ensure the queue worker is running: `php artisan queue:work --queue=csv,shopify`
3. Click **CSV Uploads → Upload CSV** in the sidebar
4. Upload the provided `shopify-products-csv.csv` file
5. Collection ID is pre-filled as `464337174767` — leave it
6. Click **Start Import**
7. Watch the live progress bar update every 3 seconds
8. Once status shows **Completed**, click **Products** in the sidebar
9. All 10 products should show status **Synced** with a Shopify ID
10. Verify in Shopify Admin: `https://laravel-import-test.myshopify.com/admin/products`

### Test 2 — Upsert (upload same CSV twice)

1. Upload the same CSV file a second time
2. Wait for the queue to process
3. Go to **Products** — total product count should remain **10**, not increase to 20
4. The `shopify_action` column should show **updated** for the second run
5. Confirm in Shopify Admin that no duplicate products were created

### Test 3 — Error handling

1. Open `.env` and set `SHOPIFY_ACCESS_TOKEN=invalid_token`
2. Run `php artisan config:clear && php artisan config:cache`
3. Upload the CSV — products will fail after 5 retry attempts
4. Go to **Error Logs** — Shopify errors will be listed with messages
5. Go to **Products** — all products show status **Failed**
6. Restore the correct token, clear config cache again
7. Click **Retry** on any failed product — it will re-sync successfully

### Test 4 — Dashboard & logs

1. Go to **Dashboard** — KPI cards show totals: uploads, synced, success rate, errors
2. Go to **Error Logs** — filter by type (validation / shopify / system)
3. Go to **Activity Logs** — filter by level or event to see the full audit trail
4. Each log entry shows the event, message, linked upload, and timestamp

### Test 5 — Validation errors

1. Create a CSV with a row missing the `Title` column or with `Price` set to `abc`
2. Upload it — the invalid rows will be skipped
3. Go to **Error Logs** — validation errors appear with the exact row number and message
4. Valid rows in the same file will still be imported successfully

---

## Project Structure

```
app/
├── Console/Commands/
│   └── FetchShopifyLocation.php     # Artisan command to get location GID
├── Http/Controllers/Admin/
│   ├── AdminAuthController.php      # Login / logout
│   ├── DashboardController.php      # KPI stats and recent activity
│   ├── UploadController.php         # CSV upload CRUD
│   ├── ProductController.php        # Product listing, detail, retry
│   ├── ImportController.php         # Live status JSON endpoint
│   ├── ErrorLogController.php       # Error log viewer
│   └── ActivityLogController.php    # Activity log viewer
├── Jobs/
│   ├── ProcessCsvImport.php         # Parses CSV, creates product records
│   └── SyncProductToShopify.php     # Syncs one product via GraphQL
├── Models/
│   ├── Upload.php
│   ├── Product.php
│   ├── ErrorLog.php
│   └── ActivityLog.php
└── Services/
    └── ShopifyService.php           # All Shopify GraphQL logic

config/
└── shopify.php                      # Shopify API configuration

database/migrations/
├── create_uploads_table.php
├── create_products_table.php
├── create_error_logs_table.php
└── create_activity_logs_table.php

resources/views/admin/
├── layouts/admin.blade.php          # Master layout with sidebar
├── login.blade.php                  # Login page
├── dashboard.blade.php              # KPI dashboard
├── uploads/ (index, create, show)
├── products/ (index, show)
├── error-logs/ (index)
└── activity-logs/ (index)

routes/
└── web.php                          # All admin routes
```

---

## Demo Video

[https://veed.io/view/4f7b483f-9bdf-4915-a510-b46eadb34929]

