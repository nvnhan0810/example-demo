<?php

namespace Modules\Order\Application\Commands\PayOrder;

final class PayOrderCommand
{
    public function __construct(
        public readonly int $orderId,
        public readonly int $userId,
    ) {}
}
