<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->constrained('uploads')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('sku')->nullable();
            $table->string('vendor')->nullable();
            $table->string('product_type')->nullable();
            $table->string('tags')->nullable();
            $table->decimal('compare_at_price', 10, 2)->nullable();
            $table->unsignedInteger('inventory_quantity')->default(0);
            $table->string('shopify_product_id')->nullable();
            $table->enum('status', ['pending', 'synced', 'failed', 'skipped'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};