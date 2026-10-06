<?php

namespace Modules\Order\Domain\Order;

final class Order
{
    /**
     * @param  list<OrderItem>  $items
     */
    public function __construct(
        public readonly ?int $id,
        public readonly string $number,
        public readonly int $userId,
        public readonly OrderStatus $status,
        public readonly PaymentStatus $paymentStatus,
        public readonly PaymentMethod $paymentMethod,
        public readonly string $subtotal,
        public readonly string $total,
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly string $customerPhone,
        public readonly string $shippingAddress,
        public readonly string $shippingCity,
        public readonly ?string $note,
        public readonly ?string $paymentReference,
        public readonly ?string $paidAt,
        public readonly ?string $createdAt,
        public readonly array $items,
    ) {}

    public function canPay(): bool
    {
        return $this->paymentStatus === PaymentStatus::Unpaid
            && $this->status === OrderStatus::PendingPayment
            && $this->paymentMethod->requiresOnlinePayment();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'user_id' => $this->userId,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'payment_status' => $this->paymentStatus->value,
            'payment_status_label' => $this->paymentStatus->label(),
            'payment_method' => $this->paymentMethod->value,
            'payment_method_label' => $this->paymentMethod->label(),
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            'customer_name' => $this->customerName,
            'customer_email' => $this->customerEmail,
            'customer_phone' => $this->customerPhone,
            'shipping_address' => $this->shippingAddress,
            'shipping_city' => $this->shippingCity,
            'note' => $this->note,
            'payment_reference' => $this->paymentReference,
            'paid_at' => $this->paidAt,
            'created_at' => $this->createdAt,
            'can_pay' => $this->canPay(),
            'items' => array_map(
                static fn (OrderItem $item): array => $item->toArray(),
                $this->items,
            ),
        ];
    }
}
