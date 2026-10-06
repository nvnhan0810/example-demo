<?php

namespace Modules\Catalog\Domain\Product;

final class Product
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $sku,
        public readonly ?string $shortDescription,
        public readonly ?string $description,
        public readonly string $price,
        public readonly ?string $compareAtPrice,
        public readonly int $stockQuantity,
        public readonly string $category,
        public readonly ?string $brand,
        public readonly ?string $imageUrl,
        public readonly ProductStatus $status,
        public readonly string $ratingAvg,
        public readonly int $soldCount,
    ) {}

    public function isAvailable(): bool
    {
        return $this->status === ProductStatus::Active && $this->stockQuantity > 0;
    }

    public function hasDiscount(): bool
    {
        if ($this->compareAtPrice === null) {
            return false;
        }

        return (float) $this->compareAtPrice > (float) $this->price;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'short_description' => $this->shortDescription,
            'description' => $this->description,
            'price' => $this->price,
            'compare_at_price' => $this->compareAtPrice,
            'stock_quantity' => $this->stockQuantity,
            'category' => $this->category,
            'brand' => $this->brand,
            'image_url' => $this->imageUrl,
            'status' => $this->status->value,
            'rating_avg' => $this->ratingAvg,
            'sold_count' => $this->soldCount,
            'is_available' => $this->isAvailable(),
            'has_discount' => $this->hasDiscount(),
        ];
    }
}
