<?php

namespace Modules\Cart\Application\Commands\UpdateCartItem;

final class UpdateCartItemCommand
{
    public function __construct(
        public readonly int $productId,
        public readonly int $quantity,
    ) {}
}
