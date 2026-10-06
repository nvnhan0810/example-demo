<?php

namespace Modules\Order\Application\Commands\PayOrder;

use Modules\Order\Domain\Order\Order;
use Modules\Order\Domain\Order\OrderStatus;
use Modules\Order\Domain\Order\PaymentStatus;
use Modules\Order\Domain\Order\Ports\OrderRepository;
use RuntimeException;

final class PayOrderHandler
{
    public function __construct(
        private readonly OrderRepository $orders,
    ) {}

    public function handle(PayOrderCommand $command): Order
    {
        $order = $this->orders->findByIdForUser($command->orderId, $command->userId);

        if ($order === null) {
            throw new RuntimeException('Không tìm thấy đơn hàng.');
        }

        if (! $order->canPay()) {
            throw new RuntimeException('Đơn hàng không thể thanh toán.');
        }

        $paid = new Order(
            id: $order->id,
            number: $order->number,
            userId: $order->userId,
            status: OrderStatus::Confirmed,
            paymentStatus: PaymentStatus::Paid,
            paymentMethod: $order->paymentMethod,
            subtotal: $order->subtotal,
            total: $order->total,
            customerName: $order->customerName,
            customerEmail: $order->customerEmail,
            customerPhone: $order->customerPhone,
            shippingAddress: $order->shippingAddress,
            shippingCity: $order->shippingCity,
            note: $order->note,
            paymentReference: 'PAY-'.strtoupper(bin2hex(random_bytes(4))),
            paidAt: now()->toIso8601String(),
            createdAt: $order->createdAt,
            items: $order->items,
        );

        return $this->orders->save($paid);
    }
}
