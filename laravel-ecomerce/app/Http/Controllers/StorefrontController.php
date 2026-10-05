<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Catalog\Queries\GetProductsQuery;
use Modules\Catalog\Handlers\GetProductsHandler;
use Modules\Cart\Commands\AddToCartCommand;
use Modules\Cart\Handlers\AddToCartHandler;

class StorefrontController extends Controller
{
    public function index(GetProductsHandler $queryHandler)
    {
        $products = $queryHandler->handle(new GetProductsQuery());

        return Inertia::render('Storefront/Index', [
            'products' => $products,
            'cartCount' => array_sum(session('cart', []))
        ]);
    }

    public function addToCart(Request $request, AddToCartHandler $commandHandler)
    {
        $request->validate(['product_id' => 'required|integer']);

        $command = new AddToCartCommand($request->product_id);
        $commandHandler->handle($command);

        return redirect()->back(); 
    }
}