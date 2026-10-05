<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Áo thun cơ bản', 'price' => 150000],
            ['name' => 'Quần Jean nam', 'price' => 350000],
            ['name' => 'Giày Sneaker', 'price' => 550000],
            ['name' => 'Balo chống nước', 'price' => 250000],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}