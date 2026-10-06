<?php

namespace App\Http\Controllers;

use App\Models\Product; // Đảm bảo đã import Model
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    // Hiển thị form tạo mới
    public function create()
    {
        return Inertia::render('Products/Create');
    }

    // Xử lý lưu sản phẩm mới
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        Product::create($validated);

        return redirect()->route('storefront.index'); 
        // Trong thực tế bạn có thể redirect về danh sách: redirect()->route('products.index')
    }

    // Hiển thị form edit với dữ liệu cũ
    public function edit(Product $product)
    {
        return Inertia::render('Products/Edit', [
            'product' => $product
        ]);
    }

    // Xử lý cập nhật dữ liệu
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        $product->update($validated);

        return redirect()->route('products.edit', $product->id);
    }
}