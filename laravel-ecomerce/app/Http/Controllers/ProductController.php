<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Application\Commands\CreateProduct\CreateProductCommand;
use Modules\Catalog\Application\Commands\CreateProduct\CreateProductHandler;
use Modules\Catalog\Application\Commands\UpdateProduct\UpdateProductCommand;
use Modules\Catalog\Application\Commands\UpdateProduct\UpdateProductHandler;
use Modules\Catalog\Application\Queries\GetProduct\GetProductHandler;
use Modules\Catalog\Application\Queries\GetProduct\GetProductQuery;
use Modules\Catalog\Domain\Product\ProductBrand;
use Modules\Catalog\Domain\Product\ProductCategory;
use Modules\Catalog\Domain\Product\ProductStatus;

class ProductController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Products/Create', [
            'categories' => ProductCategory::all(),
            'brands' => ProductBrand::all(),
            'statuses' => ProductStatus::values(),
        ]);
    }

    public function store(Request $request, CreateProductHandler $handler): RedirectResponse
    {
        $validated = $this->validatedPayload($request);

        $handler->handle(new CreateProductCommand(
            name: $validated['name'],
            price: (string) $validated['price'],
            category: $validated['category'],
            shortDescription: $validated['short_description'] ?? null,
            description: $validated['description'] ?? null,
            compareAtPrice: isset($validated['compare_at_price']) ? (string) $validated['compare_at_price'] : null,
            stockQuantity: (int) $validated['stock_quantity'],
            brand: $validated['brand'] ?? null,
            imageUrl: $validated['image_url'] ?? null,
            status: $validated['status'],
        ));

        return redirect()->route('storefront.index');
    }

    public function edit(int $product, GetProductHandler $queryHandler): Response
    {
        $item = $queryHandler->handle(new GetProductQuery($product));

        return Inertia::render('Products/Edit', [
            'product' => $item->toArray(),
            'categories' => ProductCategory::all(),
            'brands' => ProductBrand::all(),
            'statuses' => ProductStatus::values(),
        ]);
    }

    public function update(
        Request $request,
        int $product,
        UpdateProductHandler $handler,
    ): RedirectResponse {
        $validated = $this->validatedPayload($request);

        $handler->handle(new UpdateProductCommand(
            productId: $product,
            name: $validated['name'],
            price: (string) $validated['price'],
            category: $validated['category'],
            shortDescription: $validated['short_description'] ?? null,
            description: $validated['description'] ?? null,
            compareAtPrice: isset($validated['compare_at_price']) ? (string) $validated['compare_at_price'] : null,
            stockQuantity: (int) $validated['stock_quantity'],
            brand: $validated['brand'] ?? null,
            imageUrl: $validated['image_url'] ?? null,
            status: $validated['status'],
        ));

        return redirect()->route('products.edit', $product);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request): array
    {
        $request->merge([
            'short_description' => $this->blankToNull($request->input('short_description')),
            'description' => $this->blankToNull($request->input('description')),
            'compare_at_price' => $this->blankToNull($request->input('compare_at_price')),
            'brand' => $this->blankToNull($request->input('brand')),
            'image_url' => $this->blankToNull($request->input('image_url')),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'category' => ['required', 'string', Rule::in(ProductCategory::all())],
            'brand' => ['nullable', 'string', 'max:100'],
            'image_url' => ['nullable', 'string', 'max:500', 'url'],
            'status' => ['required', 'string', Rule::in(ProductStatus::values())],
        ]);
    }

    private function blankToNull(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) && trim($value) === '') {
            return null;
        }

        return $value;
    }
}
