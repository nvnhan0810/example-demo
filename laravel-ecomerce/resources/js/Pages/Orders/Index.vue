<template>
  <div class="min-h-screen bg-[radial-gradient(circle_at_top,_#dff3f0_0%,_#f7fafc_42%,_#eef3f6_100%)] text-[var(--color-ink)]">
    <StoreHeader />

    <main class="mx-auto max-w-6xl px-4 py-10">
      <h1 class="font-[family-name:var(--font-display)] text-3xl md:text-4xl">Lịch sử đơn hàng</h1>
      <p class="mt-2 text-sm text-slate-600">Theo dõi trạng thái và thanh toán các đơn đã đặt.</p>

      <div v-if="orders.length > 0" class="mt-8 space-y-3">
        <Link
          v-for="order in orders"
          :key="order.id"
          :href="`/orders/${order.id}`"
          class="block rounded-2xl border border-[var(--color-sand)] bg-white/90 p-4 transition hover:border-[var(--color-leaf)] hover:shadow-sm"
        >
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <p class="font-semibold">{{ order.number }}</p>
              <p class="mt-1 text-xs text-slate-500">
                {{ formatDate(order.created_at) }} · {{ order.items.length }} dòng hàng
              </p>
            </div>
            <div class="text-right">
              <p class="font-bold text-[var(--color-ember)]">{{ formatPrice(order.total) }}</p>
              <p class="mt-1 text-xs text-slate-600">{{ order.status_label }} · {{ order.payment_status_label }}</p>
            </div>
          </div>
        </Link>
      </div>

      <div
        v-else
        class="mt-10 rounded-2xl border border-dashed border-[var(--color-sand)] bg-white/60 px-6 py-16 text-center text-slate-600"
      >
        Bạn chưa có đơn hàng nào.
        <div class="mt-4">
          <Link
            href="/"
            class="inline-block rounded-xl bg-[var(--color-leaf)] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[var(--color-leaf-dark)]"
          >
            Mua sắm ngay
          </Link>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import StoreHeader from '../../Components/StoreHeader.vue'
import { formatPrice } from '../../types/product'
import type { Order } from '../../types/order'

defineProps<{
  orders: Order[]
}>()

const formatDate = (value: string | null): string => {
  if (!value) {
    return '—'
  }

  return new Intl.DateTimeFormat('vi-VN', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value))
}
</script>
