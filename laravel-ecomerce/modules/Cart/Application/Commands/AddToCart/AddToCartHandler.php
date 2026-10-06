<?php

namespace Modules\Cart\Application\Commands\AddToCart;

use Modules\Cart\Domain\Cart\Ports\CartRepository;
use Modules\Catalog\Domain\Product\Ports\ProductRepository;
use RuntimeException;

final class AddToCartHandler
{
    public function __construct(
        private readonly CartRepository $cart,
        private readonly ProductRepository $products,
    ) {}

    public function handle(AddToCartCommand $command): void
    {
        $product = $this->products->findById($command->productId);

        if ($product === null || ! $product->isAvailable()) {
            throw new RuntimeException('Sản phẩm không khả dụng.');
        }

        $quantity = max(1, $command->quantity);
        $current = $this->cart->quantities()[$command->productId] ?? 0;

        if (($current + $quantity) > $product->stockQuantity) {
            throw new RuntimeException('Số lượng vượt quá tồn kho.');
        }

        $this->cart->add($command->productId, $quantity);
    }
}
