<?php

namespace Modules\Order\Domain\Order;

final class OrderItem
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?int $productId,
        public readonly string $productName,
        public readonly string $productSku,
        public readonly string $unitPrice,
        public readonly int $quantity,
        public readonly string $lineTotal,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'product_sku' => $this->productSku,
            'unit_price' => $this->unitPrice,
            'quantity' => $this->quantity,
            'line_total' => $this->lineTotal,
        ];
    }
}
