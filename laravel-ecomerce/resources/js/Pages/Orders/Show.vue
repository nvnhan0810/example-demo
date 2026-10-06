<template>
  <div class="min-h-screen bg-[radial-gradient(circle_at_top,_#dff3f0_0%,_#f7fafc_42%,_#eef3f6_100%)] text-[var(--color-ink)]">
    <StoreHeader />

    <main class="mx-auto max-w-6xl px-4 py-10">
      <Link href="/orders" class="text-sm text-slate-600 hover:text-[var(--color-leaf)]">← Lịch sử đơn</Link>
      <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="font-[family-name:var(--font-display)] text-3xl">{{ order.number }}</h1>
          <p class="mt-2 text-sm text-slate-600">
            {{ formatDate(order.created_at) }} · {{ order.status_label }} · {{ order.payment_status_label }}
          </p>
        </div>
        <Link
          v-if="order.can_pay"
          :href="`/orders/${order.id}/pay`"
          class="rounded-xl bg-[var(--color-leaf)] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[var(--color-leaf-dark)]"
        >
          Thanh toán ngay
        </Link>
      </div>

      <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl border border-[var(--color-sand)] bg-white/90 p-5">
          <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Sản phẩm</h2>
          <ul class="mt-4 space-y-3">
            <li
              v-for="item in order.items"
              :key="`${item.id}-${item.product_sku}`"
              class="flex justify-between gap-3 border-b border-[var(--color-sand)]/70 pb-3 text-sm last:border-0"
            >
              <div>
                <p class="font-medium">{{ item.product_name }}</p>
                <p class="text-xs text-slate-500">{{ item.product_sku }} · ×{{ item.quantity }}</p>
              </div>
              <p class="font-semibold">{{ formatPrice(item.line_total) }}</p>
            </li>
          </ul>
          <div class="mt-4 flex justify-between text-sm font-bold">
            <span>Tổng cộng</span>
            <span class="text-[var(--color-ember)]">{{ formatPrice(order.total) }}</span>
          </div>
        </section>

        <section class="space-y-4">
          <div class="rounded-2xl border border-[var(--color-sand)] bg-white/90 p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Giao hàng</h2>
            <dl class="mt-4 space-y-2 text-sm">
              <div><dt class="inline text-slate-500">Người nhận:</dt> {{ order.customer_name }}</div>
              <div><dt class="inline text-slate-500">SĐT:</dt> {{ order.customer_phone }}</div>
              <div><dt class="inline text-slate-500">Email:</dt> {{ order.customer_email }}</div>
              <div><dt class="inline text-slate-500">Địa chỉ:</dt> {{ order.shipping_address }}, {{ order.shipping_city }}</div>
              <div v-if="order.note"><dt class="inline text-slate-500">Ghi chú:</dt> {{ order.note }}</div>
            </dl>
          </div>

          <div class="rounded-2xl border border-[var(--color-sand)] bg-white/90 p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Thanh toán</h2>
            <dl class="mt-4 space-y-2 text-sm">
              <div><dt class="inline text-slate-500">Phương thức:</dt> {{ order.payment_method_label }}</div>
              <div><dt class="inline text-slate-500">Trạng thái:</dt> {{ order.payment_status_label }}</div>
              <div v-if="order.payment_reference">
                <dt class="inline text-slate-500">Mã giao dịch:</dt> {{ order.payment_reference }}
              </div>
              <div v-if="order.paid_at">
                <dt class="inline text-slate-500">Thanh toán lúc:</dt> {{ formatDate(order.paid_at) }}
              </div>
            </dl>
          </div>
        </section>
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
  order: Order
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
