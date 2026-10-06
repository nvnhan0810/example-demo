<template>
  <div class="min-h-screen bg-[radial-gradient(circle_at_top,_#dff3f0_0%,_#f7fafc_45%,_#eef3f6_100%)] text-[var(--color-ink)]">
    <StoreHeader>
      <template #center>
        <Link href="/" class="text-sm text-slate-600 hover:text-[var(--color-leaf)]">← Cửa hàng</Link>
      </template>
    </StoreHeader>

    <main class="mx-auto grid max-w-6xl gap-8 px-4 py-10 lg:grid-cols-2">
      <div class="overflow-hidden rounded-3xl border border-[var(--color-sand)] bg-white shadow-sm">
        <div class="relative aspect-square bg-[var(--color-mist)]">
          <img
            :src="product.image_url ?? placeholderImage"
            :alt="product.name"
            class="h-full w-full object-cover"
          />
          <span
            v-if="percent !== null"
            class="absolute left-4 top-4 rounded-md bg-[var(--color-ember)] px-2.5 py-1 text-sm font-bold text-white"
          >
            -{{ percent }}%
          </span>
        </div>
      </div>

      <div class="flex flex-col">
        <p class="text-sm font-medium uppercase tracking-[0.16em] text-[var(--color-leaf)]">
          {{ product.category }}
        </p>
        <h1 class="mt-2 font-[family-name:var(--font-display)] text-3xl leading-tight md:text-4xl">
          {{ product.name }}
        </h1>
        <p class="mt-2 text-sm text-slate-500">
          {{ product.brand ?? 'NovaMart' }} · SKU {{ product.sku }}
        </p>

        <div class="mt-5 flex items-end gap-3">
          <span class="text-3xl font-bold text-[var(--color-ember)]">
            {{ formatPrice(product.price) }}
          </span>
          <span
            v-if="product.compare_at_price"
            class="pb-1 text-base text-slate-400 line-through"
          >
            {{ formatPrice(product.compare_at_price) }}
          </span>
        </div>

        <p class="mt-3 text-sm text-slate-600">
          ★ {{ Number(product.rating_avg).toFixed(1) }}
          · Đã bán {{ product.sold_count.toLocaleString('vi-VN') }}
          · Kho {{ product.stock_quantity.toLocaleString('vi-VN') }}
        </p>

        <p v-if="product.short_description" class="mt-5 text-base leading-relaxed text-slate-700">
          {{ product.short_description }}
        </p>

        <div class="mt-8 flex flex-wrap gap-3">
          <button
            type="button"
            class="rounded-xl bg-[var(--color-leaf)] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[var(--color-leaf-dark)] disabled:cursor-not-allowed disabled:bg-slate-300"
            :disabled="isAdding || !product.is_available"
            @click="addToCart"
          >
            {{ addLabel }}
          </button>
          <Link
            href="/cart"
            class="rounded-xl border border-[var(--color-sand)] bg-white px-5 py-3 text-sm font-medium hover:bg-[var(--color-mist)]"
          >
            Xem giỏ
          </Link>
          <Link
            :href="`/products/${product.id}/edit`"
            class="rounded-xl border border-[var(--color-sand)] bg-white px-5 py-3 text-sm font-medium hover:bg-[var(--color-mist)]"
          >
            Chỉnh sửa
          </Link>
        </div>

        <div
          v-if="product.description"
          class="mt-10 rounded-2xl border border-[var(--color-sand)] bg-white/80 p-5"
        >
          <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
            Mô tả sản phẩm
          </h2>
          <p class="whitespace-pre-line text-sm leading-relaxed text-slate-700">
            {{ product.description }}
          </p>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StoreHeader from '../../Components/StoreHeader.vue'
import { discountPercent, formatPrice, type Product } from '../../types/product'

const props = defineProps<{
  product: Product
}>()

const isAdding = ref(false)
const placeholderImage = 'https://picsum.photos/seed/novamart/600/600'
const percent = computed((): number | null => discountPercent(props.product))

const addLabel = computed((): string => {
  if (!props.product.is_available) {
    return 'Hết hàng'
  }

  if (isAdding.value) {
    return 'Đang thêm...'
  }

  return 'Thêm vào giỏ'
})

const addToCart = (): void => {
  isAdding.value = true
  router.post('/cart', { product_id: props.product.id }, {
    preserveScroll: true,
    onFinish: () => {
      isAdding.value = false
    },
  })
}
</script>
