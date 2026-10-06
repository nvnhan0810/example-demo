<template>
  <div class="min-h-screen bg-[radial-gradient(circle_at_top,_#dff3f0_0%,_#f7fafc_42%,_#eef3f6_100%)] text-[var(--color-ink)]">
    <StoreHeader />

    <main class="mx-auto max-w-6xl px-4 py-10">
      <h1 class="font-[family-name:var(--font-display)] text-3xl md:text-4xl">Thanh toán</h1>
      <p class="mt-2 text-sm text-slate-600">Điền thông tin giao hàng và chọn phương thức thanh toán.</p>

      <form class="mt-8 grid gap-6 lg:grid-cols-[1fr_340px]" @submit.prevent="submit">
        <div class="space-y-4 rounded-2xl border border-[var(--color-sand)] bg-white/90 p-5">
          <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Thông tin giao hàng</h2>

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium" for="customer_name">Họ tên</label>
              <input
                id="customer_name"
                v-model="form.customer_name"
                required
                class="w-full rounded-xl border border-[var(--color-sand)] px-3 py-2.5 text-sm outline-none ring-[var(--color-leaf)] focus:ring-2"
              />
              <p v-if="form.errors.customer_name" class="mt-1 text-sm text-red-600">{{ form.errors.customer_name }}</p>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium" for="customer_phone">Số điện thoại</label>
              <input
                id="customer_phone"
                v-model="form.customer_phone"
                required
                class="w-full rounded-xl border border-[var(--color-sand)] px-3 py-2.5 text-sm outline-none ring-[var(--color-leaf)] focus:ring-2"
              />
              <p v-if="form.errors.customer_phone" class="mt-1 text-sm text-red-600">{{ form.errors.customer_phone }}</p>
            </div>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium" for="customer_email">Email</label>
            <input
              id="customer_email"
              v-model="form.customer_email"
              type="email"
              required
              class="w-full rounded-xl border border-[var(--color-sand)] px-3 py-2.5 text-sm outline-none ring-[var(--color-leaf)] focus:ring-2"
            />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium" for="shipping_address">Địa chỉ</label>
            <input
              id="shipping_address"
              v-model="form.shipping_address"
              required
              class="w-full rounded-xl border border-[var(--color-sand)] px-3 py-2.5 text-sm outline-none ring-[var(--color-leaf)] focus:ring-2"
            />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium" for="shipping_city">Tỉnh / Thành phố</label>
            <input
              id="shipping_city"
              v-model="form.shipping_city"
              required
              class="w-full rounded-xl border border-[var(--color-sand)] px-3 py-2.5 text-sm outline-none ring-[var(--color-leaf)] focus:ring-2"
            />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium" for="note">Ghi chú</label>
            <textarea
              id="note"
              v-model="form.note"
              rows="3"
              class="w-full rounded-xl border border-[var(--color-sand)] px-3 py-2.5 text-sm outline-none ring-[var(--color-leaf)] focus:ring-2"
            />
          </div>

          <div>
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Thanh toán</h2>
            <div class="space-y-2">
              <label
                v-for="method in paymentMethods"
                :key="method.value"
                class="flex cursor-pointer items-center gap-3 rounded-xl border border-[var(--color-sand)] px-3 py-3 text-sm transition"
                :class="form.payment_method === method.value ? 'border-[var(--color-leaf)] bg-[var(--color-mist)]' : 'hover:bg-[var(--color-mist)]'"
              >
                <input v-model="form.payment_method" type="radio" :value="method.value" required />
                {{ method.label }}
              </label>
            </div>
          </div>
        </div>

        <aside class="h-fit rounded-2xl border border-[var(--color-sand)] bg-white/90 p-5">
          <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Đơn hàng</h2>
          <ul class="mt-4 space-y-3">
            <li
              v-for="item in cart.items"
              :key="item.product_id"
              class="flex justify-between gap-3 text-sm"
            >
              <span class="text-slate-700">{{ item.name }} × {{ item.quantity }}</span>
              <span class="font-medium">{{ formatPrice(item.line_total) }}</span>
            </li>
          </ul>
          <div class="mt-4 flex items-center justify-between border-t border-[var(--color-sand)] pt-4 text-sm">
            <span>Tổng cộng</span>
            <span class="text-lg font-bold text-[var(--color-ember)]">{{ formatPrice(cart.subtotal) }}</span>
          </div>
          <button
            type="submit"
            class="mt-5 w-full rounded-xl bg-[var(--color-leaf)] px-4 py-3 text-sm font-semibold text-white hover:bg-[var(--color-leaf-dark)] disabled:opacity-60"
            :disabled="form.processing"
          >
            {{ form.processing ? 'Đang tạo đơn...' : 'Đặt hàng' }}
          </button>
        </aside>
      </form>
    </main>
  </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import StoreHeader from '../../Components/StoreHeader.vue'
import { formatPrice } from '../../types/product'
import type { Cart } from '../../types/cart'
import type { PaymentMethodOption } from '../../types/order'

const props = defineProps<{
  cart: Cart
  paymentMethods: PaymentMethodOption[]
  defaults: {
    customer_name: string
    customer_email: string
  }
}>()

const form = useForm({
  customer_name: props.defaults.customer_name,
  customer_email: props.defaults.customer_email,
  customer_phone: '',
  shipping_address: '',
  shipping_city: '',
  payment_method: props.paymentMethods[0]?.value ?? 'cod',
  note: '',
})

const submit = (): void => {
  form.post('/checkout')
}
</script>
