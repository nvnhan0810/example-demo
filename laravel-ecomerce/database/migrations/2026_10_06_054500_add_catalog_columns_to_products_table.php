<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->string('sku', 64)->nullable()->after('slug');
            $table->string('short_description', 500)->nullable()->after('sku');
            $table->text('description')->nullable()->after('short_description');
            $table->decimal('compare_at_price', 12, 2)->nullable()->after('price');
            $table->unsignedInteger('stock_quantity')->default(0)->after('compare_at_price');
            $table->string('category', 100)->nullable()->after('stock_quantity');
            $table->string('brand', 100)->nullable()->after('category');
            $table->string('image_url', 500)->nullable()->after('brand');
            $table->string('status', 32)->default('active')->after('image_url');
            $table->decimal('rating_avg', 2, 1)->default(0)->after('status');
            $table->unsignedInteger('sold_count')->default(0)->after('rating_avg');
        });

        DB::table('products')->orderBy('id')->each(function (object $product): void {
            $slugBase = Str::slug((string) $product->name) ?: 'product';

            DB::table('products')->where('id', $product->id)->update([
                'slug' => $slugBase.'-'.$product->id,
                'sku' => 'SKU-'.$product->id,
                'category' => 'general',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->unique()->change();
            $table->string('sku', 64)->nullable(false)->unique()->change();
            $table->decimal('price', 12, 2)->change();
            $table->string('category', 100)->nullable(false)->change();

            $table->index('status');
            $table->index('category');
            $table->index(['status', 'category']);
            $table->index('sold_count');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['category']);
            $table->dropIndex(['status', 'category']);
            $table->dropIndex(['sold_count']);

            $table->dropUnique(['slug']);
            $table->dropUnique(['sku']);

            $table->dropColumn([
                'slug',
                'sku',
                'short_description',
                'description',
                'compare_at_price',
                'stock_quantity',
                'category',
                'brand',
                'image_url',
                'status',
                'rating_avg',
                'sold_count',
            ]);

            $table->decimal('price', 10, 2)->change();
        });
    }
};
