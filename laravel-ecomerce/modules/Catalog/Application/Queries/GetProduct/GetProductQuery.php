<?php

namespace Modules\Catalog\Application\Queries\GetProduct;

final class GetProductQuery
{
    public function __construct(
        public readonly int $productId,
    ) {}
}
