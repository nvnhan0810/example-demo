<?php

namespace Modules\Catalog\Domain\Product;

final class ProductPage
{
    /**
     * @param  list<Product>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
    ) {}

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / max(1, $this->perPage)));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'data' => array_map(
                static fn (Product $product): array => $product->toArray(),
                $this->items,
            ),
            'meta' => [
                'total' => $this->total,
                'page' => $this->page,
                'per_page' => $this->perPage,
                'last_page' => $this->lastPage(),
            ],
        ];
    }
}
