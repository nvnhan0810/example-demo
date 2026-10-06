<?php

namespace Modules\Cart\Domain\Cart;

final class CartItem
{
    public function __construct(
        public readonly int $productId,
        public readonly string $name,
        public readonly string $sku,
        public readonly string $price,
        public readonly ?string $imageUrl,
        public readonly int $stockQuantity,
        public readonly int $quantity,
        public readonly bool $isAvailable,
    ) {}

    public function lineTotal(): string
    {
        return number_format((float) $this->price * $this->quantity, 2, '.', '');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'name' => $this->name,
            'sku' => $this->sku,
            'price' => $this->price,
            'image_url' => $this->imageUrl,
            'stock_quantity' => $this->stockQuantity,
            'quantity' => $this->quantity,
            'line_total' => $this->lineTotal(),
            'is_available' => $this->isAvailable,
        ];
    }
}
