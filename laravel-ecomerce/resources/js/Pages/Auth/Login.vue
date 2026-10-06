<template>
  <div class="min-h-screen bg-[radial-gradient(circle_at_top,_#dff3f0_0%,_#f7fafc_42%,_#eef3f6_100%)] text-[var(--color-ink)]">
    <StoreHeader />

    <main class="mx-auto flex max-w-md flex-col px-4 py-12">
      <h1 class="font-[family-name:var(--font-display)] text-3xl">Đăng nhập</h1>
      <p class="mt-2 text-sm text-slate-600">
        Đăng nhập để đặt hàng và xem lịch sử đơn.
      </p>

      <form class="mt-8 space-y-4 rounded-2xl border border-[var(--color-sand)] bg-white/90 p-6" @submit.prevent="submit">
        <div>
          <label class="mb-1 block text-sm font-medium" for="email">Email</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            required
            class="w-full rounded-xl border border-[var(--color-sand)] px-3 py-2.5 text-sm outline-none ring-[var(--color-leaf)] focus:ring-2"
          />
          <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium" for="password">Mật khẩu</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            required
            class="w-full rounded-xl border border-[var(--color-sand)] px-3 py-2.5 text-sm outline-none ring-[var(--color-leaf)] focus:ring-2"
          />
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
          <input v-model="form.remember" type="checkbox" class="rounded border-slate-300" />
          Ghi nhớ đăng nhập
        </label>

        <button
          type="submit"
          class="w-full rounded-xl bg-[var(--color-leaf)] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[var(--color-leaf-dark)] disabled:opacity-60"
          :disabled="form.processing"
        >
          {{ form.processing ? 'Đang đăng nhập...' : 'Đăng nhập' }}
        </button>
      </form>

      <p class="mt-4 text-center text-sm text-slate-600">
        Chưa có tài khoản?
        <Link href="/register" class="font-medium text-[var(--color-leaf)] hover:underline">Đăng ký</Link>
      </p>
      <p class="mt-2 text-center text-xs text-slate-500">
        Demo: demo@novamart.test / password
      </p>
    </main>
  </div>
</template>

<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import StoreHeader from '../../Components/StoreHeader.vue'

const form = useForm({
  email: 'demo@novamart.test',
  password: 'password',
  remember: true,
})

const submit = (): void => {
  form.post('/login')
}
</script>
