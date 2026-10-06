<?php

namespace Modules\Cart\Application\Commands\AddToCart;

final class AddToCartCommand
{
    public function __construct(
        public readonly int $productId,
        public readonly int $quantity = 1,
    ) {}
}
