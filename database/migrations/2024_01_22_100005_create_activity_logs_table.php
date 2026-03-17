<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event');                           // e.g. 'file_uploaded', 'job_started'
            $table->string('level')->default('info');          // info, warning, error, debug
            $table->text('message');
            $table->foreignId('upload_id')->nullable()->constrained('uploads')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->json('context')->nullable();               // additional data
            $table->timestamps();

            $table->index(['event', 'created_at']);
            $table->index(['level', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};