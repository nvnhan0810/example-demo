<?php
namespace Modules\Cart\Commands;

class AddToCartCommand
{
    public function __construct(
        public readonly int $productId,
        public readonly int $quantity = 1
    ) {}
}