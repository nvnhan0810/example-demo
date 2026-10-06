<?php

namespace Modules\Cart\Application\Commands\RemoveFromCart;

final class RemoveFromCartCommand
{
    public function __construct(
        public readonly int $productId,
    ) {}
}
