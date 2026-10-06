<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Cart\Application\Queries\GetCart\GetCartHandler;
use Modules\Cart\Application\Queries\GetCart\GetCartQuery;
use Modules\Order\Application\Commands\PlaceOrder\PlaceOrderCommand;
use Modules\Order\Application\Commands\PlaceOrder\PlaceOrderHandler;
use Modules\Order\Domain\Order\PaymentMethod;
use RuntimeException;

class CheckoutController extends Controller
{
    public function create(Request $request, GetCartHandler $getCart): Response|RedirectResponse
    {
        $cart = $getCart->handle(new GetCartQuery);

        if ($cart->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng trống.');
        }

        $user = $request->user();

        return Inertia::render('Checkout/Index', [
            'cart' => $cart->toArray(),
            'paymentMethods' => array_map(
                static fn (PaymentMethod $method): array => [
                    'value' => $method->value,
                    'label' => $method->label(),
                ],
                PaymentMethod::cases(),
            ),
            'defaults' => [
                'customer_name' => $user?->name ?? '',
                'customer_email' => $user?->email ?? '',
            ],
        ]);
    }

    public function store(Request $request, PlaceOrderHandler $handler): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:32'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'shipping_city' => ['required', 'string', 'max:120'],
            'payment_method' => ['required', 'string', Rule::in(PaymentMethod::values())],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $order = $handler->handle(new PlaceOrderCommand(
                userId: (int) $request->user()->id,
                customerName: $validated['customer_name'],
                customerEmail: $validated['customer_email'],
                customerPhone: $validated['customer_phone'],
                shippingAddress: $validated['shipping_address'],
                shippingCity: $validated['shipping_city'],
                paymentMethod: $validated['payment_method'],
                note: $validated['note'] ?? null,
            ));
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        if ($order->canPay()) {
            return redirect()
                ->route('orders.pay.show', $order->id)
                ->with('success', 'Đơn hàng đã tạo. Vui lòng thanh toán để hoàn tất.');
        }

        return redirect()
            ->route('orders.show', $order->id)
            ->with('success', 'Đặt hàng thành công. Bạn sẽ thanh toán khi nhận hàng.');
    }
}
