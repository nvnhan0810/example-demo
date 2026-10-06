<?php

namespace Modules\Cart\Domain\Cart;

final class Cart
{
    /**
     * @param  list<CartItem>  $items
     */
    public function __construct(
        public readonly array $items,
    ) {}

    public function itemCount(): int
    {
        return array_sum(array_map(
            static fn (CartItem $item): int => $item->quantity,
            $this->items,
        ));
    }

    public function subtotal(): string
    {
        $total = 0.0;

        foreach ($this->items as $item) {
            $total += (float) $item->lineTotal();
        }

        return number_format($total, 2, '.', '');
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'items' => array_map(
                static fn (CartItem $item): array => $item->toArray(),
                $this->items,
            ),
            'item_count' => $this->itemCount(),
            'subtotal' => $this->subtotal(),
            'is_empty' => $this->isEmpty(),
        ];
    }
}
