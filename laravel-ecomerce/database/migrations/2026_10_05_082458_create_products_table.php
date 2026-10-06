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
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku', 64)->unique();
            $table->string('short_description', 500)->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->string('category', 100);
            $table->string('brand', 100)->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('status', 32)->default('active');
            $table->decimal('rating_avg', 2, 1)->default(0);
            $table->unsignedInteger('sold_count')->default(0);
            $table->timestamps();

            $table->index('status');
            $table->index('category');
            $table->index(['status', 'category']);
            $table->index('sold_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
