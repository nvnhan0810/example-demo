<?php

namespace Modules\Cart\Application\Queries\GetCart;

use Modules\Cart\Domain\Cart\Cart;
use Modules\Cart\Domain\Cart\CartItem;
use Modules\Cart\Domain\Cart\Ports\CartRepository;
use Modules\Catalog\Domain\Product\Ports\ProductRepository;

final class GetCartHandler
{
    public function __construct(
        private readonly CartRepository $cart,
        private readonly ProductRepository $products,
    ) {}

    public function handle(GetCartQuery $query): Cart
    {
        $quantities = $this->cart->quantities();

        if ($quantities === []) {
            return new Cart([]);
        }

        $products = $this->products->findByIds(array_keys($quantities));
        $items = [];

        foreach ($quantities as $productId => $quantity) {
            $product = $products[$productId] ?? null;

            if ($product === null) {
                $this->cart->remove($productId);

                continue;
            }

            $items[] = new CartItem(
                productId: $productId,
                name: $product->name,
                sku: $product->sku,
                price: $product->price,
                imageUrl: $product->imageUrl,
                stockQuantity: $product->stockQuantity,
                quantity: $quantity,
                isAvailable: $product->isAvailable(),
            );
        }

        return new Cart($items);
    }
}
