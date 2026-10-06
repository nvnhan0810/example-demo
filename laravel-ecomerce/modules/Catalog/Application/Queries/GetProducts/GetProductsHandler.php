<?php

namespace Modules\Catalog\Application\Queries\GetProducts;

use Modules\Catalog\Domain\Product\Ports\ProductRepository;
use Modules\Catalog\Domain\Product\ProductPage;
use Modules\Catalog\Domain\Product\ProductStatus;

final class GetProductsHandler
{
    public function __construct(
        private readonly ProductRepository $products,
    ) {}

    public function handle(GetProductsQuery $query): ProductPage
    {
        return $this->products->paginate(
            page: max(1, $query->page),
            perPage: min(200, max(1, $query->perPage)),
            category: $query->category,
            search: $query->search,
            status: ProductStatus::Active,
        );
    }
}
