<script setup lang="ts">
import { computed, nextTick, onUnmounted, ref, watch } from 'vue'
import { toast } from 'vue3-toastify'
import { useRouter } from 'vue-router'
import EmailVerificationBanner from '@/components/merchant/EmailVerificationBanner.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const isLoggingOut = ref(false)
const mobileNavOpen = ref(false)
const closeButtonRef = ref<HTMLButtonElement | null>(null)

const isEmailVerified = computed(() => !auth.user?.is_email_verified)

const stats = [
  { label: 'Total Orders', value: '—', icon: '📦', note: 'Connect backend in Part 13' },
  { label: 'Total Revenue', value: '—', icon: '💰', note: 'Connect backend in Part 13' },
  { label: 'Active Products', value: '—', icon: '🛍️', note: 'Connect backend in Part 13' },
  { label: 'Customers', value: '—', icon: '👥', note: 'Connect backend in Part 13' },
]

const navItems = [
  { label: 'Dashboard', icon: '📊', active: true },
  { label: 'Products', icon: '🛍️', active: false },
  { label: 'Orders', icon: '📦', active: false },
  { label: 'Customers', icon: '👥', active: false },
  { label: 'Analytics', icon: '📈', active: false },
  { label: 'Settings', icon: '⚙️', active: false },
]

function onKeydown(e: KeyboardEvent) { if (e.key === 'Escape') closeNav() }

function closeNav() {
  mobileNavOpen.value = false
  window.removeEventListener('keydown', onKeydown)
}

async function openNav() {
  mobileNavOpen.value = true
  window.removeEventListener('keydown', onKeydown) // guard against duplicate listeners
  window.addEventListener('keydown', onKeydown)
  await nextTick()
  closeButtonRef.value?.focus()
}

// Lock body scroll while drawer is open
watch(mobileNavOpen, open => {
  document.body.style.overflow = open ? 'hidden' : ''
})

onUnmounted(() => {
  window.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''
})

async function logout() {
  isLoggingOut.value = true
  try {
    await auth.logout()
    toast.success('Signed out successfully.')
    await router.push('/merchant/login')
  }
  catch {
    toast.error('Could not sign out. Please try again.')
  }
  finally {
    isLoggingOut.value = false
  }
}
</script>

<template>
  <div class="theme-merchant min-h-screen bg-emerald-950 text-white flex">

    <!-- ── Mobile nav (teleported to body to avoid z-index stacking issues) ── -->
    <Teleport to="body">
      <!-- Backdrop: fades independently -->
      <Transition
        enter-active-class="transition-opacity duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="mobileNavOpen"
          class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden"
          aria-hidden="true"
          @click="closeNav"
        />
      </Transition>

      <!-- Drawer: always in DOM, slides via CSS transform — no Transition needed -->
      <aside
        id="mobile-nav"
        :inert="!mobileNavOpen"
        class="fixed inset-y-0 left-0 z-50 w-72 bg-emerald-900 border-r border-emerald-800
               flex flex-col lg:hidden
               transition-transform duration-300 ease-in-out"
        :class="mobileNavOpen ? 'translate-x-0' : '-translate-x-full'"
        aria-label="Navigation"
      >
        <!-- Logo + close -->
        <div class="h-14 flex items-center justify-between px-4 border-b border-emerald-800 shrink-0">
          <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-emerald-600 flex items-center justify-center">
              <span class="text-white font-bold text-xs">R</span>
            </div>
            <span class="font-bold text-white text-sm">RBAS Merchant</span>
          </div>
          <button
            ref="closeButtonRef"
            class="text-emerald-400 hover:text-white p-1.5 rounded-lg transition-colors"
            aria-label="Close menu"
            @click="closeNav"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Nav items -->
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
          <button
            v-for="item in navItems"
            :key="item.label"
            class="w-full flex items-center gap-3 px-3 py-3 rounded-lg text-sm transition-colors"
            :class="item.active
              ? 'bg-emerald-700/60 text-white font-medium'
              : 'text-emerald-400 hover:text-white hover:bg-emerald-800/60'"
            @click="closeNav"
          >
            <span>{{ item.icon }}</span>
            {{ item.label }}
          </button>
        </nav>

        <!-- User + logout -->
        <div class="p-4 border-t border-emerald-800 shrink-0">
          <div class="flex items-center gap-3 mb-3 px-1">
            <div class="w-8 h-8 rounded-full bg-emerald-700 flex items-center justify-center text-sm font-semibold shrink-0">
              {{ auth.user?.first_name?.charAt(0).toUpperCase() }}
            </div>
            <div class="min-w-0">
              <p class="text-sm font-medium text-white truncate">{{ auth.user?.first_name }}</p>
              <p class="text-xs text-emerald-400 truncate">{{ auth.user?.email }}</p>
            </div>
          </div>
          <Button
            variant="ghost"
            class="w-full text-emerald-400 hover:text-white hover:bg-emerald-800/60 text-sm justify-start"
            :disabled="isLoggingOut"
            @click="logout"
          >
            {{ isLoggingOut ? 'Signing out…' : '← Sign out' }}
          </Button>
        </div>
      </aside>
    </Teleport>

    <!-- ── Desktop sidebar ───────────────────────────────────────── -->
    <aside class="hidden lg:flex flex-col w-64 bg-emerald-900/80 border-r border-emerald-800 min-h-screen shrink-0">
      <div class="h-16 flex items-center gap-3 px-6 border-b border-emerald-800">
        <div class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center">
          <span class="text-white font-bold text-sm">R</span>
        </div>
        <span class="font-bold text-white">RBAS Merchant</span>
      </div>

      <nav class="flex-1 px-3 py-4 space-y-1">
        <button
          v-for="item in navItems"
          :key="item.label"
          class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors"
          :class="item.active
            ? 'bg-emerald-700/60 text-white font-medium'
            : 'text-emerald-400 hover:text-white hover:bg-emerald-800/60'"
        >
          <span>{{ item.icon }}</span>
          {{ item.label }}
        </button>
      </nav>

      <div class="p-4 border-t border-emerald-800">
        <div class="flex items-center gap-3 mb-3 px-1">
          <div class="w-8 h-8 rounded-full bg-emerald-700 flex items-center justify-center text-sm font-semibold shrink-0">
            {{ auth.user?.first_name?.charAt(0).toUpperCase() }}
          </div>
          <div class="min-w-0">
            <p class="text-sm font-medium text-white truncate">{{ auth.user?.first_name }}</p>
            <p class="text-xs text-emerald-400 truncate">{{ auth.user?.email }}</p>
          </div>
        </div>
        <Button
          variant="ghost"
          class="w-full text-emerald-400 hover:text-white hover:bg-emerald-800/60 text-sm justify-start"
          :disabled="isLoggingOut"
          @click="logout"
        >
          {{ isLoggingOut ? 'Signing out…' : '← Sign out' }}
        </Button>
      </div>
    </aside>

    <!-- ── Main area ─────────────────────────────────────────────── -->
    <div class="flex-1 flex flex-col min-h-screen min-w-0">

      <!-- Mobile top bar -->
      <header class="lg:hidden h-14 flex items-center justify-between px-4 bg-emerald-900/80 border-b border-emerald-800 shrink-0">
        <div class="flex items-center gap-3">
          <button
            class="text-emerald-400 hover:text-white p-1.5 rounded-lg transition-colors"
            aria-label="Open menu"
            :aria-expanded="mobileNavOpen"
            aria-controls="mobile-nav"
            @click="openNav"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
          <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-emerald-600 flex items-center justify-center">
              <span class="text-white font-bold text-xs">R</span>
            </div>
            <span class="font-semibold text-white text-sm">RBAS Merchant</span>
          </div>
        </div>
        <Button
          variant="ghost"
          size="sm"
          class="text-emerald-400 hover:text-white text-xs"
          :disabled="isLoggingOut"
          @click="logout"
        >
          Sign out
        </Button>
      </header>

      <!-- Page content -->
      <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-auto">

        <!-- Email verification + rate limit banners -->
        <Transition
          enter-active-class="transition-all duration-300 ease-out"
          enter-from-class="opacity-0 -translate-y-2"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition-all duration-200 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-2"
        >
          <EmailVerificationBanner
            v-if="isEmailVerified"
            :email="auth.user?.email"
          />
        </Transition>

        <!-- Page heading -->
        <div class="mb-6 lg:mb-8">
          <h1 class="text-xl sm:text-2xl font-bold text-white">
            Good day, {{ auth.user?.first_name }} 👋
          </h1>
          <p class="text-emerald-400 mt-1 text-sm">
            Here's your merchant overview
          </p>
        </div>

        <!-- Stats grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-6 lg:mb-8">
          <Card
            v-for="stat in stats"
            :key="stat.label"
            class="bg-emerald-900/50 border-emerald-800 text-white rounded-xl"
          >
            <CardHeader class="pb-2">
              <div class="flex items-center justify-between">
                <CardTitle class="text-xs font-medium text-emerald-400 uppercase tracking-wider">
                  {{ stat.label }}
                </CardTitle>
                <span class="text-lg">{{ stat.icon }}</span>
              </div>
            </CardHeader>
            <CardContent>
              <p class="text-2xl sm:text-3xl font-bold text-white">
                {{ stat.value }}
              </p>
              <p class="text-xs text-emerald-600 mt-1">
                {{ stat.note }}
              </p>
            </CardContent>
          </Card>
        </div>

        <!-- Placeholder content area -->
        <Card class="bg-emerald-900/30 border-emerald-800/60 border-dashed rounded-xl">
          <CardContent class="flex flex-col items-center justify-center py-12 lg:py-16 text-center">
            <span class="text-4xl mb-4">📊</span>
            <h3 class="text-lg font-semibold text-emerald-200 mb-2">
              Dashboard coming to life in Part 13
            </h3>
            <p class="text-sm text-emerald-500 max-w-sm">
              Stats, charts, and order management will be wired to the backend API in the next part.
            </p>
          </CardContent>
        </Card>
      </main>
    </div>
  </div>
</template>
