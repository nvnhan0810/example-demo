<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Catalog\Domain\Product\ProductStatus;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'short_description',
        'description',
        'price',
        'compare_at_price',
        'stock_quantity',
        'category',
        'brand',
        'image_url',
        'status',
        'rating_avg',
        'sold_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'rating_avg' => 'decimal:1',
            'stock_quantity' => 'integer',
            'sold_count' => 'integer',
            'status' => ProductStatus::class,
        ];
    }
}
