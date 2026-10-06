<?php

namespace Modules\Cart\Application\Commands\RemoveFromCart;

use Modules\Cart\Domain\Cart\Ports\CartRepository;

final class RemoveFromCartHandler
{
    public function __construct(
        private readonly CartRepository $cart,
    ) {}

    public function handle(RemoveFromCartCommand $command): void
    {
        $this->cart->remove($command->productId);
    }
}
