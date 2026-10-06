<?php

namespace Modules\Catalog\Application\Queries\GetProduct;

use Modules\Catalog\Domain\Product\Ports\ProductRepository;
use Modules\Catalog\Domain\Product\Product;
use RuntimeException;

final class GetProductHandler
{
    public function __construct(
        private readonly ProductRepository $products,
    ) {}

    public function handle(GetProductQuery $query): Product
    {
        $product = $this->products->findById($query->productId);

        if ($product === null) {
            throw new RuntimeException('Product not found.');
        }

        return $product;
    }
}
