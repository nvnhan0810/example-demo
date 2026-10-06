<template>
  <header class="border-b border-[var(--color-sand)]/80 bg-white/70 backdrop-blur">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-4">
      <Link href="/" class="font-[family-name:var(--font-display)] text-2xl tracking-tight text-[var(--color-ink)]">
        NovaMart
      </Link>

      <slot name="center" />

      <div class="flex flex-wrap items-center gap-2 sm:gap-3">
        <Link
          href="/cart"
          class="rounded-full bg-[var(--color-mist)] px-3 py-1.5 text-sm transition hover:bg-[var(--color-sand)]"
        >
          Giỏ · <span class="font-semibold">{{ cartCount }}</span>
        </Link>

        <template v-if="user">
          <Link
            href="/orders"
            class="rounded-xl border border-[var(--color-sand)] bg-white px-3 py-2 text-sm font-medium hover:bg-[var(--color-mist)]"
          >
            Đơn hàng
          </Link>
          <span class="hidden text-sm text-slate-600 sm:inline">{{ user.name }}</span>
          <button
            type="button"
            class="rounded-xl border border-[var(--color-sand)] bg-white px-3 py-2 text-sm font-medium hover:bg-[var(--color-mist)]"
            @click="logout"
          >
            Đăng xuất
          </button>
        </template>
        <template v-else>
          <Link
            href="/login"
            class="rounded-xl border border-[var(--color-sand)] bg-white px-3 py-2 text-sm font-medium hover:bg-[var(--color-mist)]"
          >
            Đăng nhập
          </Link>
          <Link
            href="/register"
            class="rounded-xl bg-[var(--color-leaf)] px-3 py-2 text-sm font-semibold text-white hover:bg-[var(--color-leaf-dark)]"
          >
            Đăng ký
          </Link>
        </template>

        <Link
          href="/products/create"
          class="rounded-xl border border-[var(--color-sand)] bg-white px-3 py-2 text-sm font-medium hover:bg-[var(--color-mist)]"
        >
          + Thêm SP
        </Link>
      </div>
    </div>

    <div
      v-if="flash.success || flash.error"
      class="mx-auto max-w-6xl px-4 pb-3"
    >
      <p
        v-if="flash.success"
        class="rounded-xl bg-emerald-50 px-3 py-2 text-sm text-emerald-800"
      >
        {{ flash.success }}
      </p>
      <p
        v-if="flash.error"
        class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-700"
      >
        {{ flash.error }}
      </p>
    </div>
  </header>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import type { AuthUser, FlashMessages } from '../types/auth'

const page = usePage()

const user = computed((): AuthUser | null => {
  const auth = page.props.auth as { user: AuthUser | null } | undefined
  return auth?.user ?? null
})

const cartCount = computed((): number => Number(page.props.cartCount ?? 0))

const flash = computed((): FlashMessages => {
  return (page.props.flash as FlashMessages | undefined) ?? {}
})

const logout = (): void => {
  router.post('/logout')
}
</script>
