<?php

namespace Modules\Order\Application\Commands\PlaceOrder;

use Illuminate\Support\Facades\DB;
use Modules\Cart\Application\Queries\GetCart\GetCartHandler;
use Modules\Cart\Application\Queries\GetCart\GetCartQuery;
use Modules\Cart\Domain\Cart\Ports\CartRepository;
use Modules\Catalog\Domain\Product\Ports\ProductRepository;
use Modules\Order\Domain\Order\Order;
use Modules\Order\Domain\Order\OrderItem;
use Modules\Order\Domain\Order\OrderStatus;
use Modules\Order\Domain\Order\PaymentMethod;
use Modules\Order\Domain\Order\PaymentStatus;
use Modules\Order\Domain\Order\Ports\OrderRepository;
use RuntimeException;

final class PlaceOrderHandler
{
    public function __construct(
        private readonly GetCartHandler $getCart,
        private readonly CartRepository $cart,
        private readonly ProductRepository $products,
        private readonly OrderRepository $orders,
    ) {}

    public function handle(PlaceOrderCommand $command): Order
    {
        $cart = $this->getCart->handle(new GetCartQuery);

        if ($cart->isEmpty()) {
            throw new RuntimeException('Giỏ hàng trống.');
        }

        $paymentMethod = PaymentMethod::from($command->paymentMethod);
        $items = [];

        foreach ($cart->items as $cartItem) {
            if (! $cartItem->isAvailable) {
                throw new RuntimeException("Sản phẩm «{$cartItem->name}» không còn khả dụng.");
            }

            if ($cartItem->quantity > $cartItem->stockQuantity) {
                throw new RuntimeException("Sản phẩm «{$cartItem->name}» không đủ tồn kho.");
            }

            $items[] = new OrderItem(
                id: null,
                productId: $cartItem->productId,
                productName: $cartItem->name,
                productSku: $cartItem->sku,
                unitPrice: $cartItem->price,
                quantity: $cartItem->quantity,
                lineTotal: $cartItem->lineTotal(),
            );
        }

        $subtotal = $cart->subtotal();
        $status = $paymentMethod->requiresOnlinePayment()
            ? OrderStatus::PendingPayment
            : OrderStatus::Confirmed;

        $order = new Order(
            id: null,
            number: $this->orders->nextNumber(),
            userId: $command->userId,
            status: $status,
            paymentStatus: PaymentStatus::Unpaid,
            paymentMethod: $paymentMethod,
            subtotal: $subtotal,
            total: $subtotal,
            customerName: $command->customerName,
            customerEmail: $command->customerEmail,
            customerPhone: $command->customerPhone,
            shippingAddress: $command->shippingAddress,
            shippingCity: $command->shippingCity,
            note: $command->note,
            paymentReference: null,
            paidAt: null,
            createdAt: null,
            items: $items,
        );

        return DB::transaction(function () use ($order, $items): Order {
            $created = $this->orders->create($order);

            foreach ($items as $item) {
                if ($item->productId === null) {
                    continue;
                }

                $this->products->recordSale($item->productId, $item->quantity);
            }

            $this->cart->clear();

            return $created;
        });
    }
}
