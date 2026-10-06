<template>
  <div class="min-h-screen bg-[radial-gradient(circle_at_top,_#dff3f0_0%,_#f7fafc_42%,_#eef3f6_100%)] text-[var(--color-ink)]">
    <StoreHeader />

    <main class="mx-auto flex max-w-md flex-col px-4 py-12">
      <h1 class="font-[family-name:var(--font-display)] text-3xl">Đăng ký</h1>
      <p class="mt-2 text-sm text-slate-600">
        Tạo tài khoản để mua sắm và theo dõi đơn hàng.
      </p>

      <form class="mt-8 space-y-4 rounded-2xl border border-[var(--color-sand)] bg-white/90 p-6" @submit.prevent="submit">
        <div>
          <label class="mb-1 block text-sm font-medium" for="name">Họ tên</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            required
            class="w-full rounded-xl border border-[var(--color-sand)] px-3 py-2.5 text-sm outline-none ring-[var(--color-leaf)] focus:ring-2"
          />
          <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
        </div>

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
          <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium" for="password_confirmation">Xác nhận mật khẩu</label>
          <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
            required
            class="w-full rounded-xl border border-[var(--color-sand)] px-3 py-2.5 text-sm outline-none ring-[var(--color-leaf)] focus:ring-2"
          />
        </div>

        <button
          type="submit"
          class="w-full rounded-xl bg-[var(--color-leaf)] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[var(--color-leaf-dark)] disabled:opacity-60"
          :disabled="form.processing"
        >
          {{ form.processing ? 'Đang tạo...' : 'Tạo tài khoản' }}
        </button>
      </form>

      <p class="mt-4 text-center text-sm text-slate-600">
        Đã có tài khoản?
        <Link href="/login" class="font-medium text-[var(--color-leaf)] hover:underline">Đăng nhập</Link>
      </p>
    </main>
  </div>
</template>

<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import StoreHeader from '../../Components/StoreHeader.vue'

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const submit = (): void => {
  form.post('/register')
}
</script>
