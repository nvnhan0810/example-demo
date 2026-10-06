<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Order\Application\Commands\PayOrder\PayOrderCommand;
use Modules\Order\Application\Commands\PayOrder\PayOrderHandler;
use Modules\Order\Application\Queries\GetOrder\GetOrderHandler;
use Modules\Order\Application\Queries\GetOrder\GetOrderQuery;
use Modules\Order\Application\Queries\GetOrders\GetOrdersHandler;
use Modules\Order\Application\Queries\GetOrders\GetOrdersQuery;
use RuntimeException;

class OrderController extends Controller
{
    public function index(Request $request, GetOrdersHandler $handler): Response
    {
        $orders = $handler->handle(new GetOrdersQuery(
            userId: (int) $request->user()->id,
        ));

        return Inertia::render('Orders/Index', [
            'orders' => array_map(
                static fn ($order): array => $order->toArray(),
                $orders,
            ),
        ]);
    }

    public function show(Request $request, int $order, GetOrderHandler $handler): Response
    {
        try {
            $item = $handler->handle(new GetOrderQuery(
                orderId: $order,
                userId: (int) $request->user()->id,
            ));
        } catch (RuntimeException) {
            abort(404);
        }

        return Inertia::render('Orders/Show', [
            'order' => $item->toArray(),
        ]);
    }

    public function showPay(Request $request, int $order, GetOrderHandler $handler): Response|RedirectResponse
    {
        try {
            $item = $handler->handle(new GetOrderQuery(
                orderId: $order,
                userId: (int) $request->user()->id,
            ));
        } catch (RuntimeException) {
            abort(404);
        }

        if (! $item->canPay()) {
            return redirect()
                ->route('orders.show', $item->id)
                ->with('error', 'Đơn hàng này không cần thanh toán online.');
        }

        return Inertia::render('Orders/Pay', [
            'order' => $item->toArray(),
        ]);
    }

    public function pay(Request $request, int $order, PayOrderHandler $handler): RedirectResponse
    {
        try {
            $paid = $handler->handle(new PayOrderCommand(
                orderId: $order,
                userId: (int) $request->user()->id,
            ));
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('orders.show', $paid->id)
            ->with('success', 'Thanh toán thành công.');
    }
}
