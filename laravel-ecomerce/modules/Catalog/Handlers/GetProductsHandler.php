<?php
namespace Modules\Catalog\Handlers;

use App\Models\Product; // Sử dụng Model từ App
use Modules\Catalog\Queries\GetProductsQuery;

class GetProductsHandler
{
    public function handle(GetProductsQuery $query)
    {
        return Product::latest()->get();
    }
}