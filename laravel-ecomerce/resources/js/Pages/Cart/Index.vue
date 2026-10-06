<template>
  <div class="min-h-screen bg-[radial-gradient(circle_at_top,_#dff3f0_0%,_#f7fafc_42%,_#eef3f6_100%)] text-[var(--color-ink)]">
    <StoreHeader />

    <main class="mx-auto max-w-6xl px-4 py-10">
      <h1 class="font-[family-name:var(--font-display)] text-3xl md:text-4xl">Giỏ hàng</h1>
      <p class="mt-2 text-sm text-slate-600">{{ cart.item_count }} sản phẩm trong giỏ</p>

      <div v-if="!cart.is_empty" class="mt-8 grid gap-6 lg:grid-cols-[1fr_320px]">
        <div class="space-y-3">
          <article
            v-for="item in cart.items"
            :key="item.product_id"
            class="flex flex-col gap-4 rounded-2xl border border-[var(--color-sand)] bg-white/90 p-4 sm:flex-row sm:items-center"
          >
            <img
              :src="item.image_url ?? placeholderImage"
              :alt="item.name"
              class="h-24 w-24 rounded-xl object-cover bg-[var(--color-mist)]"
            />
            <div class="min-w-0 flex-1">
              <Link :href="`/products/${item.product_id}`" class="font-medium hover:text-[var(--color-leaf)]">
                {{ item.name }}
              </Link>
              <p class="mt-1 text-xs text-slate-500">SKU {{ item.sku }}</p>
              <p class="mt-2 font-semibold text-[var(--color-ember)]">{{ formatPrice(item.price) }}</p>
              <p v-if="!item.is_available" class="mt-1 text-xs text-red-600">Sản phẩm hiện không khả dụng</p>
            </div>
            <div class="flex items-center gap-3">
              <input
                type="number"
                min="1"
                :max="item.stock_quantity"
                :value="item.quantity"
                class="w-20 rounded-lg border border-[var(--color-sand)] px-2 py-2 text-sm"
                @change="updateQty(item.product_id, ($event.target as HTMLInputElement).value)"
              />
              <button
                type="button"
                class="text-sm text-red-600 hover:underline"
                @click="removeItem(item.product_id)"
              >
                Xóa
              </button>
            </div>
            <p class="min-w-24 text-right text-sm font-semibold">
              {{ formatPrice(item.line_total) }}
            </p>
          </article>
        </div>

        <aside class="h-fit rounded-2xl border border-[var(--color-sand)] bg-white/90 p-5">
          <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Tóm tắt</h2>
          <div class="mt-4 flex items-center justify-between text-sm">
            <span>Tạm tính</span>
            <span class="font-semibold">{{ formatPrice(cart.subtotal) }}</span>
          </div>
          <p class="mt-2 text-xs text-slate-500">Phí vận chuyển tính khi xác nhận đơn.</p>
          <Link
            href="/checkout"
            class="mt-5 block rounded-xl bg-[var(--color-leaf)] px-4 py-3 text-center text-sm font-semibold text-white hover:bg-[var(--color-leaf-dark)]"
          >
            Tiến hành đặt hàng
          </Link>
          <Link href="/" class="mt-3 block text-center text-sm text-slate-600 hover:text-[var(--color-leaf)]">
            ← Tiếp tục mua sắm
          </Link>
        </aside>
      </div>

      <div
        v-else
        class="mt-10 rounded-2xl border border-dashed border-[var(--color-sand)] bg-white/60 px-6 py-16 text-center"
      >
        <p class="text-slate-600">Giỏ hàng đang trống.</p>
        <Link
          href="/"
          class="mt-4 inline-block rounded-xl bg-[var(--color-leaf)] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[var(--color-leaf-dark)]"
        >
          Khám phá sản phẩm
        </Link>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import StoreHeader from '../../Components/StoreHeader.vue'
import { formatPrice } from '../../types/product'
import type { Cart } from '../../types/cart'

defineProps<{
  cart: Cart
}>()

const placeholderImage = 'https://picsum.photos/seed/novamart/200/200'

const updateQty = (productId: number, raw: string): void => {
  const quantity = Number.parseInt(raw, 10)
  if (Number.isNaN(quantity)) {
    return
  }

  router.patch('/cart', { product_id: productId, quantity }, { preserveScroll: true })
}

const removeItem = (productId: number): void => {
  router.delete('/cart', {
    data: { product_id: productId },
    preserveScroll: true,
  })
}
</script>
