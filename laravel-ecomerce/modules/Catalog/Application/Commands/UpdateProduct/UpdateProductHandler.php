<?php

namespace Modules\Catalog\Application\Commands\UpdateProduct;

use Illuminate\Support\Str;
use Modules\Catalog\Domain\Product\Ports\ProductRepository;
use Modules\Catalog\Domain\Product\Product;
use Modules\Catalog\Domain\Product\ProductStatus;
use RuntimeException;

final class UpdateProductHandler
{
    public function __construct(
        private readonly ProductRepository $products,
    ) {}

    public function handle(UpdateProductCommand $command): Product
    {
        $existing = $this->products->findById($command->productId);

        if ($existing === null) {
            throw new RuntimeException('Product not found.');
        }

        $slugBase = Str::slug($command->name);
        $slug = $slugBase !== ''
            ? $slugBase.'-'.$existing->id
            : 'product-'.$existing->id;

        $product = new Product(
            id: $existing->id,
            name: $command->name,
            slug: $slug,
            sku: $existing->sku,
            shortDescription: $command->shortDescription,
            description: $command->description,
            price: $command->price,
            compareAtPrice: $command->compareAtPrice,
            stockQuantity: max(0, $command->stockQuantity),
            category: $command->category,
            brand: $command->brand,
            imageUrl: $command->imageUrl,
            status: ProductStatus::from($command->status),
            ratingAvg: $existing->ratingAvg,
            soldCount: $existing->soldCount,
        );

        return $this->products->update($product);
    }
}
