<?php

namespace Modules\Order\Application\Queries\GetOrders;

use Modules\Order\Domain\Order\Order;
use Modules\Order\Domain\Order\Ports\OrderRepository;

final class GetOrdersHandler
{
    public function __construct(
        private readonly OrderRepository $orders,
    ) {}

    /**
     * @return list<Order>
     */
    public function handle(GetOrdersQuery $query): array
    {
        return $this->orders->listForUser($query->userId);
    }
}
