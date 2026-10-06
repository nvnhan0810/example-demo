<?php

namespace Modules\Order\Domain\Order\Ports;

use Modules\Order\Domain\Order\Order;

interface OrderRepository
{
    public function create(Order $order): Order;

    public function findByIdForUser(int $orderId, int $userId): ?Order;

    /**
     * @return list<Order>
     */
    public function listForUser(int $userId): array;

    public function save(Order $order): Order;

    public function nextNumber(): string;
}
