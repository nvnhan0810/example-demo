<?php

namespace Modules\Catalog\Application\Commands\CreateProduct;

use Illuminate\Support\Str;
use Modules\Catalog\Domain\Product\Ports\ProductRepository;
use Modules\Catalog\Domain\Product\Product;
use Modules\Catalog\Domain\Product\ProductStatus;

final class CreateProductHandler
{
    public function __construct(
        private readonly ProductRepository $products,
    ) {}

    public function handle(CreateProductCommand $command): Product
    {
        $sequence = $this->products->nextSkuSequence();
        $sku = sprintf('SKU-%08d', $sequence);
        $slugBase = Str::slug($command->name);
        $slug = $slugBase !== '' ? $slugBase.'-'.$sequence : 'product-'.$sequence;

        $product = new Product(
            id: null,
            name: $command->name,
            slug: $slug,
            sku: $sku,
            shortDescription: $command->shortDescription,
            description: $command->description,
            price: $command->price,
            compareAtPrice: $command->compareAtPrice,
            stockQuantity: max(0, $command->stockQuantity),
            category: $command->category,
            brand: $command->brand,
            imageUrl: $command->imageUrl,
            status: ProductStatus::from($command->status),
            ratingAvg: '0.0',
            soldCount: 0,
        );

        return $this->products->create($product);
    }
}
