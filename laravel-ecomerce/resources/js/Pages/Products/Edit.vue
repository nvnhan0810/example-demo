<template>
  <div class="min-h-screen bg-[var(--color-foam)] px-4 py-10 text-[var(--color-ink)]">
    <div class="mx-auto max-w-2xl rounded-3xl border border-[var(--color-sand)] bg-white p-6 shadow-sm md:p-8">
      <div class="mb-6 flex items-center justify-between gap-3">
        <div>
          <p class="text-sm font-medium uppercase tracking-[0.16em] text-[var(--color-leaf)]">Catalog</p>
          <h1 class="font-[family-name:var(--font-display)] text-3xl">Sửa sản phẩm</h1>
          <p class="mt-1 text-sm text-slate-500">SKU {{ product.sku }}</p>
        </div>
        <div class="flex gap-3 text-sm">
          <Link :href="`/products/${product.id}`" class="text-slate-600 hover:text-[var(--color-leaf)]">Xem</Link>
          <Link href="/" class="text-slate-600 hover:text-[var(--color-leaf)]">Cửa hàng</Link>
        </div>
      </div>

      <form class="space-y-4" @submit.prevent="submit">
        <div>
          <label class="mb-1.5 block text-sm font-medium" for="name">Tên sản phẩm</label>
          <input id="name" v-model="form.name" type="text" class="field" :class="{ 'field-error': form.errors.name }" />
          <p v-if="form.errors.name" class="error">{{ form.errors.name }}</p>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium" for="short_description">Mô tả ngắn</label>
          <input id="short_description" v-model="form.short_description" type="text" class="field" />
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium" for="description">Mô tả chi tiết</label>
          <textarea id="description" v-model="form.description" rows="4" class="field" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-medium" for="price">Giá bán (VNĐ)</label>
            <input id="price" v-model="form.price" type="number" min="0" step="1000" class="field" :class="{ 'field-error': form.errors.price }" />
            <p v-if="form.errors.price" class="error">{{ form.errors.price }}</p>
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium" for="compare_at_price">Giá gốc (tuỳ chọn)</label>
            <input id="compare_at_price" v-model="form.compare_at_price" type="number" min="0" step="1000" class="field" />
          </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-medium" for="stock_quantity">Tồn kho</label>
            <input id="stock_quantity" v-model="form.stock_quantity" type="number" min="0" class="field" />
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium" for="status">Trạng thái</label>
            <select id="status" v-model="form.status" class="field">
              <option v-for="status in statuses" :key="status" :value="status">{{ statusLabel(status) }}</option>
            </select>
          </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-medium" for="category">Danh mục</label>
            <select id="category" v-model="form.category" class="field">
              <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
            </select>
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium" for="brand">Thương hiệu</label>
            <select id="brand" v-model="form.brand" class="field">
              <option value="">— Chọn —</option>
              <option v-for="brand in brands" :key="brand" :value="brand">{{ brand }}</option>
            </select>
          </div>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium" for="image_url">URL ảnh</label>
          <input id="image_url" v-model="form.image_url" type="url" class="field" />
          <p v-if="form.errors.image_url" class="error">{{ form.errors.image_url }}</p>
        </div>

        <button
          type="submit"
          class="w-full rounded-xl bg-[var(--color-leaf)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[var(--color-leaf-dark)] disabled:bg-slate-300"
          :disabled="form.processing"
        >
          {{ form.processing ? 'Đang cập nhật...' : 'Cập nhật' }}
        </button>

        <p v-if="form.recentlySuccessful" class="text-center text-sm font-medium text-teal-700">
          Cập nhật thành công!
        </p>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import { ProductStatus, type Product, type ProductStatusValue } from '../../types/product'

const props = defineProps<{
  product: Product
  categories: string[]
  brands: string[]
  statuses: ProductStatusValue[]
}>()

const statusLabels: Record<ProductStatusValue, string> = {
  [ProductStatus.Active]: 'Đang bán',
  [ProductStatus.Draft]: 'Nháp',
  [ProductStatus.Archived]: 'Lưu trữ',
}

const statusLabel = (status: string): string => {
  if (status === ProductStatus.Active || status === ProductStatus.Draft || status === ProductStatus.Archived) {
    return statusLabels[status]
  }

  return status
}

const form = useForm({
  name: props.product.name,
  short_description: props.product.short_description ?? '',
  description: props.product.description ?? '',
  price: Number(props.product.price),
  compare_at_price: props.product.compare_at_price !== null ? Number(props.product.compare_at_price) : null,
  stock_quantity: props.product.stock_quantity,
  category: props.product.category,
  brand: props.product.brand ?? '',
  image_url: props.product.image_url ?? '',
  status: props.product.status,
})

const submit = (): void => {
  form.put(`/products/${props.product.id}`, {
    preserveScroll: true,
  })
}
</script>

<style scoped>
.field {
  width: 100%;
  border-radius: 0.75rem;
  border: 1px solid var(--color-sand);
  background: white;
  padding: 0.65rem 0.85rem;
  font-size: 0.925rem;
  outline: none;
}

.field:focus {
  box-shadow: 0 0 0 2px var(--color-leaf);
}

.field-error {
  border-color: #dc2626;
}

.error {
  margin-top: 0.35rem;
  font-size: 0.8rem;
  color: #dc2626;
}
</style>
