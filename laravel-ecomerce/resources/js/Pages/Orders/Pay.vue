<template>
  <div class="min-h-screen bg-[radial-gradient(circle_at_top,_#dff3f0_0%,_#f7fafc_42%,_#eef3f6_100%)] text-[var(--color-ink)]">
    <StoreHeader />

    <main class="mx-auto max-w-lg px-4 py-12">
      <h1 class="font-[family-name:var(--font-display)] text-3xl">Thanh toán đơn hàng</h1>
      <p class="mt-2 text-sm text-slate-600">
        {{ order.number }} · {{ order.payment_method_label }}
      </p>

      <div class="mt-8 rounded-2xl border border-[var(--color-sand)] bg-white/90 p-6">
        <p class="text-sm text-slate-600">Số tiền cần thanh toán</p>
        <p class="mt-1 text-3xl font-bold text-[var(--color-ember)]">{{ formatPrice(order.total) }}</p>

        <div
          v-if="order.payment_method === 'bank_transfer'"
          class="mt-5 rounded-xl bg-[var(--color-mist)] p-4 text-sm text-slate-700"
        >
          <p class="font-medium">Chuyển khoản demo</p>
          <p class="mt-2">Ngân hàng: Nova Bank</p>
          <p>STK: 0123456789</p>
          <p>Nội dung: {{ order.number }}</p>
        </div>

        <div
          v-else
          class="mt-5 rounded-xl bg-[var(--color-mist)] p-4 text-sm text-slate-700"
        >
          Đây là cổng thanh toán giả lập. Nhấn xác nhận để đánh dấu đơn đã thanh toán thành công.
        </div>

        <button
          type="button"
          class="mt-6 w-full rounded-xl bg-[var(--color-leaf)] px-4 py-3 text-sm font-semibold text-white hover:bg-[var(--color-leaf-dark)] disabled:opacity-60"
          :disabled="processing"
          @click="confirmPay"
        >
          {{ processing ? 'Đang xử lý...' : 'Xác nhận thanh toán' }}
        </button>

        <Link
          :href="`/orders/${order.id}`"
          class="mt-3 block text-center text-sm text-slate-600 hover:text-[var(--color-leaf)]"
        >
          Quay lại đơn hàng
        </Link>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StoreHeader from '../../Components/StoreHeader.vue'
import { formatPrice } from '../../types/product'
import type { Order } from '../../types/order'

const props = defineProps<{
  order: Order
}>()

const processing = ref(false)

const confirmPay = (): void => {
  processing.value = true
  router.post(`/orders/${props.order.id}/pay`, {}, {
    onFinish: () => {
      processing.value = false
    },
  })
}
</script>
