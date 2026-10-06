<template>
  <div class="min-h-screen bg-[radial-gradient(circle_at_top,_#dff3f0_0%,_#f7fafc_42%,_#eef3f6_100%)] text-[var(--color-ink)]">
    <header class="border-b border-[var(--color-sand)]/80 bg-white/70 backdrop-blur">
      <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-4">
        <Link href="/" class="font-[family-name:var(--font-display)] text-2xl tracking-tight text-[var(--color-ink)]">
          NovaMart
        </Link>

        <form class="flex min-w-[240px] flex-1 max-w-xl gap-2" @submit.prevent="search">
          <input
            v-model="searchQuery"
            type="search"
            placeholder="Tìm sản phẩm, thương hiệu, SKU..."
            class="w-full rounded-xl border border-[var(--color-sand)] bg-white px-4 py-2.5 text-sm outline-none ring-[var(--color-leaf)] focus:ring-2"
          />
          <button
            type="submit"
            class="rounded-xl bg-[var(--color-leaf)] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[var(--color-leaf-dark)]"
          >
            Tìm
          </button>
        </form>

        <div class="flex items-center gap-3">
          <div class="rounded-full bg-[var(--color-mist)] px-3 py-1.5 text-sm">
            Giỏ · <span class="font-semibold">{{ cartCount }}</span>
          </div>
          <Link
            href="/products/create"
            class="rounded-xl border border-[var(--color-sand)] bg-white px-3 py-2 text-sm font-medium hover:bg-[var(--color-mist)]"
          >
            + Thêm SP
          </Link>
        </div>
      </div>
    </header>

    <section class="mx-auto max-w-6xl px-4 pb-6 pt-10">
      <p class="mb-2 text-sm font-medium uppercase tracking-[0.18em] text-[var(--color-leaf)]">Sàn mua sắm</p>
      <h1 class="font-[family-name:var(--font-display)] text-4xl leading-tight md:text-5xl">
        NovaMart
      </h1>
      <p class="mt-3 max-w-xl text-base text-slate-600">
        Khám phá hàng ngàn sản phẩm từ thời trang đến điện tử — giá rõ ràng, giao nhanh.
      </p>
    </section>

    <div class="mx-auto flex max-w-6xl flex-col gap-6 px-4 pb-16 lg:flex-row">
      <aside class="w-full shrink-0 lg:w-56">
        <div class="sticky top-4 rounded-2xl border border-[var(--color-sand)] bg-white/80 p-4">
          <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Danh mục</h2>
          <nav class="flex flex-col gap-1">
            <button
              type="button"
              class="rounded-lg px-3 py-2 text-left text-sm transition"
              :class="!filters.category ? 'bg-[var(--color-leaf)] text-white' : 'hover:bg-[var(--color-mist)]'"
              @click="filterCategory(null)"
            >
              Tất cả
            </button>
            <button
              v-for="category in categories"
              :key="category"
              type="button"
              class="rounded-lg px-3 py-2 text-left text-sm transition"
              :class="filters.category === category ? 'bg-[var(--color-leaf)] text-white' : 'hover:bg-[var(--color-mist)]'"
              @click="filterCategory(category)"
            >
              {{ category }}
            </button>
          </nav>
        </div>
      </aside>

      <main class="min-w-0 flex-1">
        <div class="mb-4 flex items-center justify-between gap-3 text-sm text-slate-600">
          <p>
            <span class="font-semibold text-[var(--color-ink)]">{{ products.meta.total.toLocaleString('vi-VN') }}</span>
            sản phẩm
          </p>
          <p v-if="filters.q">Từ khóa: “{{ filters.q }}”</p>
        </div>

        <div
          v-if="products.data.length > 0"
          class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-4"
        >
          <article
            v-for="product in products.data"
            :key="product.id"
            class="group overflow-hidden rounded-2xl border border-[var(--color-sand)] bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
          >
            <Link :href="`/products/${product.id}`" class="block">
              <div class="relative aspect-square overflow-hidden bg-[var(--color-mist)]">
                <img
                  :src="product.image_url ?? placeholderImage"
                  :alt="product.name"
                  class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                  loading="lazy"
                />
                <span
                  v-if="discountPercent(product) !== null"
                  class="absolute left-2 top-2 rounded-md bg-[var(--color-ember)] px-2 py-0.5 text-xs font-bold text-white"
                >
                  -{{ discountPercent(product) }}%
                </span>
              </div>
              <div class="space-y-1.5 p-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">
                  {{ product.brand ?? product.category }}
                </p>
                <h3 class="line-clamp-2 min-h-10 text-sm font-medium leading-snug">
                  {{ product.name }}
                </h3>
                <div class="flex items-baseline gap-2">
                  <span class="text-base font-bold text-[var(--color-ember)]">
                    {{ formatPrice(product.price) }}
                  </span>
                  <span
                    v-if="product.compare_at_price"
                    class="text-xs text-slate-400 line-through"
                  >
                    {{ formatPrice(product.compare_at_price) }}
                  </span>
                </div>
                <p class="text-xs text-slate-500">
                  ★ {{ Number(product.rating_avg).toFixed(1) }}
                  · Đã bán {{ product.sold_count.toLocaleString('vi-VN') }}
                </p>
              </div>
            </Link>
            <div class="flex gap-2 border-t border-[var(--color-sand)] p-3 pt-2">
              <button
                type="button"
                class="flex-1 rounded-lg bg-[var(--color-leaf)] px-2 py-2 text-xs font-semibold text-white transition hover:bg-[var(--color-leaf-dark)] disabled:cursor-not-allowed disabled:bg-slate-300"
                :disabled="isAdding === product.id || !product.is_available"
                @click="addToCart(product.id)"
              >
                {{ cartLabel(product) }}
              </button>
              <Link
                :href="`/products/${product.id}/edit`"
                class="rounded-lg border border-[var(--color-sand)] px-3 py-2 text-xs font-medium text-slate-600 hover:bg-[var(--color-mist)]"
              >
                Sửa
              </Link>
            </div>
          </article>
        </div>

        <div
          v-else
          class="rounded-2xl border border-dashed border-[var(--color-sand)] bg-white/60 px-6 py-16 text-center text-slate-500"
        >
          Không tìm thấy sản phẩm phù hợp.
        </div>

        <div
          v-if="products.meta.last_page > 1"
          class="mt-8 flex items-center justify-center gap-3"
        >
          <button
            type="button"
            class="rounded-lg border border-[var(--color-sand)] bg-white px-3 py-2 text-sm disabled:opacity-40"
            :disabled="!canGoPrev"
            @click="goPage(products.meta.page - 1)"
          >
            Trước
          </button>
          <span class="text-sm text-slate-600">
            Trang {{ products.meta.page }} / {{ products.meta.last_page }}
          </span>
          <button
            type="button"
            class="rounded-lg border border-[var(--color-sand)] bg-white px-3 py-2 text-sm disabled:opacity-40"
            :disabled="!canGoNext"
            @click="goPage(products.meta.page + 1)"
          >
            Sau
          </button>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
  discountPercent,
  formatPrice,
  type Product,
  type ProductPage,
} from '../../types/product'

const props = defineProps<{
  products: ProductPage
  cartCount: number
  categories: string[]
  filters: {
    category: string | null
    q: string | null
  }
}>()

const searchQuery = ref(props.filters.q ?? '')
const isAdding = ref<number | null>(null)
const placeholderImage = 'https://picsum.photos/seed/novamart/600/600'

const canGoPrev = computed((): boolean => props.products.meta.page > 1)
const canGoNext = computed((): boolean => props.products.meta.page < props.products.meta.last_page)

const cartLabel = (product: Product): string => {
  if (!product.is_available) {
    return 'Hết hàng'
  }

  if (isAdding.value === product.id) {
    return 'Đang thêm...'
  }

  return 'Thêm giỏ'
}

const browse = (params: Record<string, string | number | null>): void => {
  router.get('/', params, {
    preserveState: true,
    preserveScroll: true,
  })
}

const search = (): void => {
  browse({
    q: searchQuery.value || null,
    category: props.filters.category,
    page: 1,
  })
}

const filterCategory = (category: string | null): void => {
  browse({
    q: props.filters.q,
    category,
    page: 1,
  })
}

const goPage = (page: number): void => {
  browse({
    q: props.filters.q,
    category: props.filters.category,
    page,
  })
}

const addToCart = (productId: number): void => {
  isAdding.value = productId
  router.post('/add-to-cart', { product_id: productId }, {
    preserveScroll: true,
    onFinish: () => {
      isAdding.value = null
    },
  })
}
</script>
