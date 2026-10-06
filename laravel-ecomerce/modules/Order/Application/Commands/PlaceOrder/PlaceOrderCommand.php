<?php

namespace Modules\Order\Application\Commands\PlaceOrder;

final class PlaceOrderCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly string $customerPhone,
        public readonly string $shippingAddress,
        public readonly string $shippingCity,
        public readonly string $paymentMethod,
        public readonly ?string $note = null,
    ) {}
}
