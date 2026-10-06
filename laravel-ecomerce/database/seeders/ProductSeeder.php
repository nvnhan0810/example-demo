<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Domain\Product\ProductBrand;
use Modules\Catalog\Domain\Product\ProductCategory;
use Modules\Catalog\Domain\Product\ProductStatus;

class ProductSeeder extends Seeder
{
    private const TOTAL_PRODUCTS = 1_000_000;

    private const CHUNK_SIZE = 1_000;

    /**
     * Seed 1,000,000 marketplace-style products in bulk chunks.
     * Run via DatabaseSeeder (master): php artisan db:seed
     * Or alone: php artisan db:seed --class=ProductSeeder
     */
    public function run(): void
    {
        $categories = ProductCategory::all();
        $brands = ProductBrand::all();
        $categoryCount = count($categories);
        $brandCount = count($brands);
        $now = now()->toDateTimeString();
        $namePrefixes = [
            'Áo', 'Quần', 'Giày', 'Túi', 'Tai nghe', 'Đồng hồ', 'Kem', 'Máy',
            'Sách', 'Ghế', 'Bình', 'Áo khoác', 'Balo', 'Loa', 'Pin', 'Camera',
        ];
        $nameSuffixes = [
            'Pro', 'Lite', 'Plus', 'Max', 'Daily', 'Urban', 'Classic', 'Prime',
            'Soft', 'Air', 'Neo', 'Essential', 'Studio', 'Travel', 'Sport',
        ];

        $this->command?->info(sprintf(
            'Seeding %s products in chunks of %s...',
            number_format(self::TOTAL_PRODUCTS),
            number_format(self::CHUNK_SIZE),
        ));

        for ($offset = 0; $offset < self::TOTAL_PRODUCTS; $offset += self::CHUNK_SIZE) {
            $rows = [];
            $limit = min(self::CHUNK_SIZE, self::TOTAL_PRODUCTS - $offset);

            for ($i = 1; $i <= $limit; $i++) {
                $n = $offset + $i;
                $price = $this->priceFor($n);
                $hasDiscount = ($n % 5) === 0;
                $prefix = $namePrefixes[$n % count($namePrefixes)];
                $suffix = $nameSuffixes[$n % count($nameSuffixes)];
                $name = sprintf('%s %s #%d', $prefix, $suffix, $n);

                $rows[] = [
                    'name' => $name,
                    'slug' => sprintf('product-%d', $n),
                    'sku' => sprintf('SKU-%08d', $n),
                    'short_description' => sprintf(
                        '%s chính hãng, giao nhanh toàn quốc.',
                        $name,
                    ),
                    'description' => sprintf(
                        "%s\n\nSản phẩm thuộc danh mục thương mại điện tử demo. Bảo hành 12 tháng, đổi trả 7 ngày.",
                        $name,
                    ),
                    'price' => $price,
                    'compare_at_price' => $hasDiscount ? round($price * 1.25, 2) : null,
                    'stock_quantity' => ($n % 97) + 1,
                    'category' => $categories[$n % $categoryCount],
                    'brand' => $brands[$n % $brandCount],
                    'image_url' => 'https://picsum.photos/seed/p'.$n.'/600/600',
                    'status' => ProductStatus::Active->value,
                    'rating_avg' => round(3.5 + (($n % 15) / 10), 1),
                    'sold_count' => ($n * 7) % 50000,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('products')->insert($rows);

            if ($this->command !== null && (($offset / self::CHUNK_SIZE) % 50) === 0) {
                $this->command->info(sprintf(
                    '  … %s / %s',
                    number_format(min($offset + self::CHUNK_SIZE, self::TOTAL_PRODUCTS)),
                    number_format(self::TOTAL_PRODUCTS),
                ));
            }

            unset($rows);
        }

        $this->command?->info('Product seeding finished.');
    }

    private function priceFor(int $n): float
    {
        $base = 49000 + (($n * 137) % 2_450_000);

        return round($base, -3) + 0.0;
    }
}
