<?php

namespace Modules\Order\Application\Queries\GetOrder;

final class GetOrderQuery
{
    public function __construct(
        public readonly int $orderId,
        public readonly int $userId,
    ) {}
}
