<?php

namespace Modules\Cart\Domain\Cart\Ports;

interface CartRepository
{
    /**
     * @return array<int, int> productId => quantity
     */
    public function quantities(): array;

    public function add(int $productId, int $quantity): void;

    public function update(int $productId, int $quantity): void;

    public function remove(int $productId): void;

    public function clear(): void;

    public function count(): int;
}
