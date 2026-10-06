<?php

namespace Modules\Order\Infrastructure\Persistence;

use App\Models\Order as OrderModel;
use App\Models\OrderItem as OrderItemModel;
use Modules\Order\Domain\Order\Order;
use Modules\Order\Domain\Order\OrderItem;
use Modules\Order\Domain\Order\OrderStatus;
use Modules\Order\Domain\Order\PaymentMethod;
use Modules\Order\Domain\Order\PaymentStatus;
use Modules\Order\Domain\Order\Ports\OrderRepository;

final class EloquentOrderRepository implements OrderRepository
{
    public function create(Order $order): Order
    {
        $model = OrderModel::query()->create([
            'number' => $order->number,
            'user_id' => $order->userId,
            'status' => $order->status->value,
            'payment_status' => $order->paymentStatus->value,
            'payment_method' => $order->paymentMethod->value,
            'subtotal' => $order->subtotal,
            'total' => $order->total,
            'customer_name' => $order->customerName,
            'customer_email' => $order->customerEmail,
            'customer_phone' => $order->customerPhone,
            'shipping_address' => $order->shippingAddress,
            'shipping_city' => $order->shippingCity,
            'note' => $order->note,
            'payment_reference' => $order->paymentReference,
            'paid_at' => $order->paidAt,
        ]);

        foreach ($order->items as $item) {
            $model->items()->create([
                'product_id' => $item->productId,
                'product_name' => $item->productName,
                'product_sku' => $item->productSku,
                'unit_price' => $item->unitPrice,
                'quantity' => $item->quantity,
                'line_total' => $item->lineTotal,
            ]);
        }

        $model->load('items');

        return $this->toDomain($model);
    }

    public function findByIdForUser(int $orderId, int $userId): ?Order
    {
        $model = OrderModel::query()
            ->with('items')
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->first();

        return $model instanceof OrderModel ? $this->toDomain($model) : null;
    }

    public function listForUser(int $userId): array
    {
        $models = OrderModel::query()
            ->with('items')
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get();

        return $models
            ->map(fn (OrderModel $model): Order => $this->toDomain($model))
            ->values()
            ->all();
    }

    public function save(Order $order): Order
    {
        $model = OrderModel::query()->findOrFail($order->id);
        $model->fill([
            'status' => $order->status->value,
            'payment_status' => $order->paymentStatus->value,
            'payment_method' => $order->paymentMethod->value,
            'subtotal' => $order->subtotal,
            'total' => $order->total,
            'customer_name' => $order->customerName,
            'customer_email' => $order->customerEmail,
            'customer_phone' => $order->customerPhone,
            'shipping_address' => $order->shippingAddress,
            'shipping_city' => $order->shippingCity,
            'note' => $order->note,
            'payment_reference' => $order->paymentReference,
            'paid_at' => $order->paidAt,
        ]);
        $model->save();
        $model->load('items');

        return $this->toDomain($model);
    }

    public function nextNumber(): string
    {
        $prefix = 'ORD-'.now()->format('Ymd').'-';
        $latest = OrderModel::query()
            ->where('number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('number');

        $sequence = 1;

        if (is_string($latest) && preg_match('/-(\d+)$/', $latest, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    private function toDomain(OrderModel $model): Order
    {
        $items = $model->items
            ->map(static function (OrderItemModel $item): OrderItem {
                return new OrderItem(
                    id: (int) $item->id,
                    productId: $item->product_id !== null ? (int) $item->product_id : null,
                    productName: (string) $item->product_name,
                    productSku: (string) $item->product_sku,
                    unitPrice: (string) $item->unit_price,
                    quantity: (int) $item->quantity,
                    lineTotal: (string) $item->line_total,
                );
            })
            ->values()
            ->all();

        return new Order(
            id: (int) $model->id,
            number: (string) $model->number,
            userId: (int) $model->user_id,
            status: $model->status instanceof OrderStatus
                ? $model->status
                : OrderStatus::from((string) $model->status),
            paymentStatus: $model->payment_status instanceof PaymentStatus
                ? $model->payment_status
                : PaymentStatus::from((string) $model->payment_status),
            paymentMethod: $model->payment_method instanceof PaymentMethod
                ? $model->payment_method
                : PaymentMethod::from((string) $model->payment_method),
            subtotal: (string) $model->subtotal,
            total: (string) $model->total,
            customerName: (string) $model->customer_name,
            customerEmail: (string) $model->customer_email,
            customerPhone: (string) $model->customer_phone,
            shippingAddress: (string) $model->shipping_address,
            shippingCity: (string) $model->shipping_city,
            note: $model->note !== null ? (string) $model->note : null,
            paymentReference: $model->payment_reference !== null ? (string) $model->payment_reference : null,
            paidAt: $model->paid_at?->toIso8601String(),
            createdAt: $model->created_at?->toIso8601String(),
            items: $items,
        );
    }
}
