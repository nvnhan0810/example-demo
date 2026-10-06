<?php

namespace Modules\Cart\Application\Commands\UpdateCartItem;

use Modules\Cart\Domain\Cart\Ports\CartRepository;
use Modules\Catalog\Domain\Product\Ports\ProductRepository;
use RuntimeException;

final class UpdateCartItemHandler
{
    public function __construct(
        private readonly CartRepository $cart,
        private readonly ProductRepository $products,
    ) {}

    public function handle(UpdateCartItemCommand $command): void
    {
        if ($command->quantity <= 0) {
            $this->cart->remove($command->productId);

            return;
        }

        $product = $this->products->findById($command->productId);

        if ($product === null) {
            $this->cart->remove($command->productId);

            return;
        }

        if ($command->quantity > $product->stockQuantity) {
            throw new RuntimeException('Số lượng vượt quá tồn kho.');
        }

        $this->cart->update($command->productId, $command->quantity);
    }
}
