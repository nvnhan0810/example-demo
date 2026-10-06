<?php

namespace Modules\Order\Application\Queries\GetOrder;

use Modules\Order\Domain\Order\Order;
use Modules\Order\Domain\Order\Ports\OrderRepository;
use RuntimeException;

final class GetOrderHandler
{
    public function __construct(
        private readonly OrderRepository $orders,
    ) {}

    public function handle(GetOrderQuery $query): Order
    {
        $order = $this->orders->findByIdForUser($query->orderId, $query->userId);

        if ($order === null) {
            throw new RuntimeException('Không tìm thấy đơn hàng.');
        }

        return $order;
    }
}
