<?php
namespace Modules\Cart\Handlers;

use Modules\Cart\Commands\AddToCartCommand;
use Illuminate\Support\Facades\Session;

class AddToCartHandler
{
    public function handle(AddToCartCommand $command): void
    {
        $cart = Session::get('cart', []);
        
        if (isset($cart[$command->productId])) {
            $cart[$command->productId] += $command->quantity;
        } else {
            $cart[$command->productId] = $command->quantity;
        }

        Session::put('cart', $cart);
    }
}