<?php

namespace Modules\Catalog\Infrastructure\Persistence;

use App\Models\Product as ProductModel;
use Modules\Catalog\Domain\Product\Ports\ProductRepository;
use Modules\Catalog\Domain\Product\Product;
use Modules\Catalog\Domain\Product\ProductPage;
use Modules\Catalog\Domain\Product\ProductStatus;
use RuntimeException;

final class EloquentProductRepository implements ProductRepository
{
    public function findById(int $id): ?Product
    {
        $model = ProductModel::query()->find($id);

        return $model instanceof ProductModel ? $this->toDomain($model) : null;
    }

    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $models = ProductModel::query()
            ->whereIn('id', $ids)
            ->get();

        $mapped = [];

        foreach ($models as $model) {
            $mapped[(int) $model->id] = $this->toDomain($model);
        }

        return $mapped;
    }

    public function paginate(
        int $page,
        int $perPage,
        ?string $category = null,
        ?string $search = null,
        ProductStatus $status = ProductStatus::Active,
    ): ProductPage {
        $builder = ProductModel::query()
            ->where('status', $status->value)
            ->orderByDesc('sold_count')
            ->orderByDesc('id');

        if ($category !== null && $category !== '') {
            $builder->where('category', $category);
        }

        if ($search !== null && $search !== '') {
            $term = '%'.$search.'%';
            $builder->where(function ($query) use ($term): void {
                $query->where('name', 'like', $term)
                    ->orWhere('sku', 'like', $term)
                    ->orWhere('brand', 'like', $term);
            });
        }

        $total = (clone $builder)->count();
        $models = $builder
            ->forPage($page, $perPage)
            ->get();

        $items = $models
            ->map(fn (ProductModel $model): Product => $this->toDomain($model))
            ->values()
            ->all();

        return new ProductPage(
            items: $items,
            total: $total,
            page: $page,
            perPage: $perPage,
        );
    }

    public function create(Product $product): Product
    {
        $model = ProductModel::query()->create($this->toAttributes($product));

        return $this->toDomain($model);
    }

    public function update(Product $product): Product
    {
        $model = ProductModel::query()->findOrFail($product->id);
        $model->fill($this->toAttributes($product));
        $model->save();
        $model->refresh();

        return $this->toDomain($model);
    }

    public function recordSale(int $productId, int $quantity): void
    {
        $model = ProductModel::query()->lockForUpdate()->find($productId);

        if (! $model instanceof ProductModel) {
            throw new RuntimeException('Sản phẩm không tồn tại.');
        }

        if ((int) $model->stock_quantity < $quantity) {
            throw new RuntimeException('Không đủ tồn kho.');
        }

        $model->stock_quantity = (int) $model->stock_quantity - $quantity;
        $model->sold_count = (int) $model->sold_count + $quantity;
        $model->save();
    }

    public function nextSkuSequence(): int
    {
        $maxId = (int) ProductModel::query()->max('id');

        return $maxId + 1;
    }

    /**
     * @return array<string, mixed>
     */
    private function toAttributes(Product $product): array
    {
        return [
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'short_description' => $product->shortDescription,
            'description' => $product->description,
            'price' => $product->price,
            'compare_at_price' => $product->compareAtPrice,
            'stock_quantity' => $product->stockQuantity,
            'category' => $product->category,
            'brand' => $product->brand,
            'image_url' => $product->imageUrl,
            'status' => $product->status->value,
            'rating_avg' => $product->ratingAvg,
            'sold_count' => $product->soldCount,
        ];
    }

    private function toDomain(ProductModel $model): Product
    {
        return new Product(
            id: (int) $model->id,
            name: (string) $model->name,
            slug: (string) $model->slug,
            sku: (string) $model->sku,
            shortDescription: $model->short_description !== null ? (string) $model->short_description : null,
            description: $model->description !== null ? (string) $model->description : null,
            price: (string) $model->price,
            compareAtPrice: $model->compare_at_price !== null ? (string) $model->compare_at_price : null,
            stockQuantity: (int) $model->stock_quantity,
            category: (string) $model->category,
            brand: $model->brand !== null ? (string) $model->brand : null,
            imageUrl: $model->image_url !== null ? (string) $model->image_url : null,
            status: $model->status instanceof ProductStatus
                ? $model->status
                : ProductStatus::from((string) $model->status),
            ratingAvg: (string) $model->rating_avg,
            soldCount: (int) $model->sold_count,
        );
    }
}
