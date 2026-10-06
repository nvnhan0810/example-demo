<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Catalog\Domain\Product\ProductBrand;
use Modules\Catalog\Domain\Product\ProductCategory;
use Modules\Catalog\Domain\Product\ProductStatus;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $price = fake()->randomFloat(2, 29000, 4999000);
        $hasDiscount = fake()->boolean(40);
        $sequence = fake()->unique()->numberBetween(1, 9_999_999);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.$sequence,
            'sku' => sprintf('SKU-%08d', $sequence),
            'short_description' => fake()->sentence(12),
            'description' => fake()->paragraphs(3, true),
            'price' => $price,
            'compare_at_price' => $hasDiscount ? round($price * fake()->randomFloat(2, 1.1, 1.6), 2) : null,
            'stock_quantity' => fake()->numberBetween(0, 500),
            'category' => fake()->randomElement(ProductCategory::all()),
            'brand' => fake()->randomElement(ProductBrand::all()),
            'image_url' => 'https://picsum.photos/seed/'.$sequence.'/600/600',
            'status' => ProductStatus::Active->value,
            'rating_avg' => fake()->randomFloat(1, 3.0, 5.0),
            'sold_count' => fake()->numberBetween(0, 50000),
        ];
    }
}
