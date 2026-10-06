<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Cart\Commands\AddToCartCommand;
use Modules\Cart\Handlers\AddToCartHandler;
use Modules\Catalog\Application\Queries\GetProduct\GetProductHandler;
use Modules\Catalog\Application\Queries\GetProduct\GetProductQuery;
use Modules\Catalog\Application\Queries\GetProducts\GetProductsHandler;
use Modules\Catalog\Application\Queries\GetProducts\GetProductsQuery;
use Modules\Catalog\Domain\Product\ProductCategory;

class StorefrontController extends Controller
{
    public function index(Request $request, GetProductsHandler $queryHandler): Response
    {
        $page = $queryHandler->handle(new GetProductsQuery(
            page: max(1, (int) $request->integer('page', 1)),
            perPage: 200,
            category: $request->string('category')->toString() ?: null,
            search: $request->string('q')->toString() ?: null,
        ));

        return Inertia::render('Storefront/Index', [
            'products' => $page->toArray(),
            'filters' => [
                'category' => $request->string('category')->toString() ?: null,
                'q' => $request->string('q')->toString() ?: null,
            ],
            'categories' => ProductCategory::all(),
            'cartCount' => array_sum(session('cart', [])),
        ]);
    }

    public function show(int $product, GetProductHandler $queryHandler): Response
    {
        $item = $queryHandler->handle(new GetProductQuery($product));

        return Inertia::render('Storefront/Show', [
            'product' => $item->toArray(),
            'cartCount' => array_sum(session('cart', [])),
        ]);
    }

    public function addToCart(Request $request, AddToCartHandler $commandHandler)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'quantity' => 'nullable|integer|min:1|max:99',
        ]);

        $command = new AddToCartCommand(
            productId: (int) $request->integer('product_id'),
            quantity: max(1, (int) $request->integer('quantity', 1)),
        );
        $commandHandler->handle($command);

        return redirect()->back();
    }
}
