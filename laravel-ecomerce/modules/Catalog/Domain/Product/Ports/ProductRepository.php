<?php

namespace Modules\Catalog\Domain\Product\Ports;

use Modules\Catalog\Domain\Product\Product;
use Modules\Catalog\Domain\Product\ProductPage;
use Modules\Catalog\Domain\Product\ProductStatus;

interface ProductRepository
{
    public function findById(int $id): ?Product;

    public function paginate(
        int $page,
        int $perPage,
        ?string $category = null,
        ?string $search = null,
        ProductStatus $status = ProductStatus::Active,
    ): ProductPage;

    public function create(Product $product): Product;

    public function update(Product $product): Product;

    public function nextSkuSequence(): int;
}
