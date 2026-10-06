<?php

namespace Modules\Catalog\Application\Queries\GetProducts;

final class GetProductsQuery
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 200,
        public readonly ?string $category = null,
        public readonly ?string $search = null,
    ) {}
}
