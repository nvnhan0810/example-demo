<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Cart\Application\Commands\AddToCart\AddToCartCommand;
use Modules\Cart\Application\Commands\AddToCart\AddToCartHandler;
use Modules\Cart\Application\Commands\RemoveFromCart\RemoveFromCartCommand;
use Modules\Cart\Application\Commands\RemoveFromCart\RemoveFromCartHandler;
use Modules\Cart\Application\Commands\UpdateCartItem\UpdateCartItemCommand;
use Modules\Cart\Application\Commands\UpdateCartItem\UpdateCartItemHandler;
use Modules\Cart\Application\Queries\GetCart\GetCartHandler;
use Modules\Cart\Application\Queries\GetCart\GetCartQuery;
use RuntimeException;

class CartController extends Controller
{
    public function index(GetCartHandler $queryHandler): Response
    {
        $cart = $queryHandler->handle(new GetCartQuery);

        return Inertia::render('Cart/Index', [
            'cart' => $cart->toArray(),
        ]);
    }

    public function store(Request $request, AddToCartHandler $handler): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        try {
            $handler->handle(new AddToCartCommand(
                productId: (int) $validated['product_id'],
                quantity: max(1, (int) ($validated['quantity'] ?? 1)),
            ));
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã thêm vào giỏ hàng.');
    }

    public function update(Request $request, UpdateCartItemHandler $handler): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        try {
            $handler->handle(new UpdateCartItemCommand(
                productId: (int) $validated['product_id'],
                quantity: (int) $validated['quantity'],
            ));
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back();
    }

    public function destroy(Request $request, RemoveFromCartHandler $handler): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
        ]);

        $handler->handle(new RemoveFromCartCommand(
            productId: (int) $validated['product_id'],
        ));

        return back()->with('success', 'Đã xóa sản phẩm khỏi giỏ.');
    }
}
