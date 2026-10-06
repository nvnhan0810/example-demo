<?php

namespace Modules\Order\Application\Queries\GetOrders;

final class GetOrdersQuery
{
    public function __construct(
        public readonly int $userId,
    ) {}
}
