<?php

namespace Modules\Cart\Infrastructure\Persistence;

use Illuminate\Support\Facades\Session;
use Modules\Cart\Domain\Cart\Ports\CartRepository;

final class SessionCartRepository implements CartRepository
{
    private const SESSION_KEY = 'cart';

    public function quantities(): array
    {
        /** @var array<int|string, int|string> $cart */
        $cart = Session::get(self::SESSION_KEY, []);
        $normalized = [];

        foreach ($cart as $productId => $quantity) {
            $id = (int) $productId;
            $qty = (int) $quantity;

            if ($id > 0 && $qty > 0) {
                $normalized[$id] = $qty;
            }
        }

        return $normalized;
    }

    public function add(int $productId, int $quantity): void
    {
        $cart = $this->quantities();
        $cart[$productId] = ($cart[$productId] ?? 0) + max(1, $quantity);
        Session::put(self::SESSION_KEY, $cart);
    }

    public function update(int $productId, int $quantity): void
    {
        $cart = $this->quantities();

        if ($quantity <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = $quantity;
        }

        Session::put(self::SESSION_KEY, $cart);
    }

    public function remove(int $productId): void
    {
        $cart = $this->quantities();
        unset($cart[$productId]);
        Session::put(self::SESSION_KEY, $cart);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function count(): int
    {
        return array_sum($this->quantities());
    }
}
