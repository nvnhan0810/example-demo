<?php

namespace Modules\Catalog\Application\Commands\UpdateProduct;

final class UpdateProductCommand
{
    public function __construct(
        public readonly int $productId,
        public readonly string $name,
        public readonly string $price,
        public readonly string $category,
        public readonly ?string $shortDescription = null,
        public readonly ?string $description = null,
        public readonly ?string $compareAtPrice = null,
        public readonly int $stockQuantity = 0,
        public readonly ?string $brand = null,
        public readonly ?string $imageUrl = null,
        public readonly string $status = 'active',
    ) {}
}
