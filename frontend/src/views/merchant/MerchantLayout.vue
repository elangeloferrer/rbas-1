<script setup lang="ts">
import {
  LayoutDashboard,
  LogOut,
  Menu,
  Moon,
  Settings,
  ShoppingBag,
  ShoppingCart,
  Sun,
  TrendingUp,
  Users,
  X,
} from '@lucide/vue'
import { computed, nextTick, onUnmounted, ref, watch } from 'vue'
import { toast } from 'vue3-toastify'
import { useRoute, useRouter } from 'vue-router'
import EmailVerificationBanner from '@/components/merchant/EmailVerificationBanner.vue'
import { Button } from '@/components/ui/button'
import { useColorMode } from '@/composables/useColorMode'
import { useFocusTrap } from '@/composables/useFocusTrap'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const { isDark, toggle } = useColorMode()
const router = useRouter()
const route = useRoute()
const isLoggingOut = ref(false)
const mobileNavOpen = ref(false)
const closeButtonRef = ref<HTMLButtonElement | null>(null)
const drawerRef = ref<HTMLElement | null>(null)
const needsEmailVerification = computed(() => auth.user?.is_email_verified === false)

const { onTab } = useFocusTrap(drawerRef)

const navItems = [
  { label: 'Dashboard', icon: LayoutDashboard, to: '/merchant/dashboard' },
  { label: 'Products', icon: ShoppingBag, to: '/merchant/products' },
  { label: 'Orders', icon: ShoppingCart, to: '' },
  { label: 'Customers', icon: Users, to: '' },
  { label: 'Analytics', icon: TrendingUp, to: '' },
  { label: 'Settings', icon: Settings, to: '' },
]

function isActive(to: string) {
  return route.path === to
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') closeNav()
  else if (e.key === 'Tab') onTab(e)
}

function closeNav() {
  mobileNavOpen.value = false
  window.removeEventListener('keydown', onKeydown)
}

async function openNav() {
  mobileNavOpen.value = true
  window.removeEventListener('keydown', onKeydown)
  window.addEventListener('keydown', onKeydown)
  await nextTick()
  closeButtonRef.value?.focus()
}

async function navigate(to: string) {
  closeNav()
  await router.push(to)
}

watch(mobileNavOpen, (open) => {
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
    await router.push('/merchant/login')
    toast.success('Signed out successfully.')
  } catch {
    toast.error('Could not sign out. Please try again.')
  } finally {
    isLoggingOut.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-background text-foreground flex">
    <!-- ── Mobile drawer ── -->
    <Teleport to="body">
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
          class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm"
          aria-hidden="true"
          @click="closeNav"
        />
      </Transition>

      <Transition
        enter-active-class="transition-transform duration-300 ease-in-out"
        enter-from-class="-translate-x-full"
        enter-to-class="translate-x-0"
        leave-active-class="transition-transform duration-300 ease-in-out"
        leave-from-class="translate-x-0"
        leave-to-class="-translate-x-full"
      >
        <aside
          v-if="mobileNavOpen"
          id="mobile-nav"
          ref="drawerRef"
          role="dialog"
          aria-modal="true"
          aria-labelledby="mobile-nav-title"
          class="fixed inset-y-0 left-0 z-50 w-72 bg-surface border-r border-surface-border flex flex-col"
        >
          <!-- Logo + close -->
          <div
            class="h-14 flex items-center justify-between px-4 border-b border-surface-border shrink-0"
          >
            <div class="flex items-center gap-2">
              <div class="w-7 h-7 rounded-lg bg-brand flex items-center justify-center">
                <span class="text-white font-bold text-xs">R</span>
              </div>
              <span id="mobile-nav-title" class="font-bold text-foreground text-sm"
                >RBAS Merchant</span
              >
            </div>
            <button
              ref="closeButtonRef"
              type="button"
              class="text-nav-text hover:text-foreground p-1.5 rounded-lg transition-colors"
              aria-label="Close menu"
              @click="closeNav"
            >
              <X class="w-5 h-5" aria-hidden="true" />
            </button>
          </div>

          <!-- Nav items -->
          <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto" aria-label="Main navigation">
            <button
              v-for="item in navItems"
              :key="item.label"
              type="button"
              class="w-full flex items-center gap-3 px-3 py-3 rounded-lg text-sm transition-colors"
              :class="
                !item.to
                  ? 'text-nav-disabled cursor-not-allowed'
                  : isActive(item.to)
                    ? 'bg-nav-active/60 text-foreground font-medium'
                    : 'text-nav-text hover:text-foreground hover:bg-nav-hover/60'
              "
              :disabled="!item.to"
              :aria-current="item.to && isActive(item.to) ? 'page' : undefined"
              @click="item.to && navigate(item.to)"
            >
              <component :is="item.icon" class="w-4 h-4 shrink-0" aria-hidden="true" />
              {{ item.label }}
            </button>
          </nav>

          <!-- User + logout -->
          <div class="p-4 border-t border-surface-border shrink-0">
            <div class="flex items-center gap-3 mb-3 px-1">
              <div
                class="w-8 h-8 rounded-full bg-nav-active flex items-center justify-center text-sm font-semibold shrink-0"
              >
                {{ auth.user?.first_name?.charAt(0).toUpperCase() }}
              </div>
              <div class="min-w-0">
                <p class="text-sm font-medium text-foreground truncate">
                  {{ auth.user?.first_name }}
                </p>
                <p class="text-xs text-nav-text truncate">{{ auth.user?.email }}</p>
              </div>
            </div>
            <Button
              variant="ghost"
              class="w-full text-nav-text hover:text-foreground hover:bg-nav-hover/60 text-sm justify-start gap-2"
              :disabled="isLoggingOut"
              @click="logout"
            >
              <LogOut class="w-4 h-4 shrink-0" aria-hidden="true" />
              {{ isLoggingOut ? 'Signing out…' : 'Sign out' }}
            </Button>
          </div>
        </aside>
      </Transition>
    </Teleport>

    <!-- ── Desktop sidebar ── -->
    <aside
      class="hidden lg:flex flex-col w-64 shrink-0 bg-surface/80 border-r border-surface-border sticky top-0 h-screen"
    >
      <!-- Logo -->
      <div class="h-16 flex items-center gap-3 px-6 border-b border-surface-border">
        <div class="w-8 h-8 rounded-lg bg-brand flex items-center justify-center">
          <span class="text-white font-bold text-sm">R</span>
        </div>
        <span class="font-bold text-foreground">RBAS Merchant</span>
      </div>

      <!-- Nav items -->
      <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto" aria-label="Main navigation">
        <button
          v-for="item in navItems"
          :key="item.label"
          type="button"
          class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors"
          :class="
            !item.to
              ? 'text-nav-disabled cursor-not-allowed'
              : isActive(item.to)
                ? 'bg-nav-active/60 text-foreground font-medium'
                : 'text-nav-text hover:text-foreground hover:bg-nav-hover/60'
          "
          :disabled="!item.to"
          :aria-current="item.to && isActive(item.to) ? 'page' : undefined"
          @click="item.to && navigate(item.to)"
        >
          <component :is="item.icon" class="w-4 h-4 shrink-0" aria-hidden="true" />
          {{ item.label }}
        </button>
      </nav>

      <!-- User + logout -->
      <div class="p-4 border-t border-surface-border">
        <div class="flex items-center gap-3 mb-3 px-1">
          <div
            class="w-8 h-8 rounded-full bg-nav-active flex items-center justify-center text-sm font-semibold shrink-0"
          >
            {{ auth.user?.first_name?.charAt(0).toUpperCase() }}
          </div>
          <div class="min-w-0">
            <p class="text-sm font-medium text-foreground truncate">{{ auth.user?.first_name }}</p>
            <p class="text-xs text-nav-text truncate">{{ auth.user?.email }}</p>
          </div>
        </div>
        <Button
          variant="ghost"
          class="w-full text-nav-text hover:text-foreground hover:bg-nav-hover/60 text-sm justify-start gap-2"
          :disabled="isLoggingOut"
          @click="logout"
        >
          <LogOut class="w-4 h-4 shrink-0" aria-hidden="true" />
          {{ isLoggingOut ? 'Signing out…' : 'Sign out' }}
        </Button>
        <Button
          variant="ghost"
          class="w-full mt-1 text-nav-text hover:text-foreground hover:bg-nav-hover/60 text-sm justify-start gap-2"
          :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
          @click="toggle"
        >
          <Sun v-if="isDark" class="w-4 h-4 shrink-0" aria-hidden="true" />
          <Moon v-else class="w-4 h-4 shrink-0" aria-hidden="true" />
          {{ isDark ? 'Light mode' : 'Dark mode' }}
        </Button>
      </div>
    </aside>

    <!-- ── Main area ── -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Mobile header -->
      <header
        class="lg:hidden sticky top-0 z-30 h-14 flex items-center justify-between px-4 bg-surface/80 border-b border-surface-border shrink-0"
      >
        <div class="flex items-center gap-3 min-w-0">
          <button
            type="button"
            class="shrink-0 text-nav-text hover:text-foreground p-1.5 rounded-lg transition-colors"
            aria-label="Open menu"
            :aria-expanded="mobileNavOpen"
            aria-controls="mobile-nav"
            @click="openNav"
          >
            <Menu class="w-5 h-5" aria-hidden="true" />
          </button>
          <div class="flex items-center gap-2 min-w-0">
            <div class="shrink-0 w-7 h-7 rounded-lg bg-brand flex items-center justify-center">
              <span class="text-white font-bold text-xs">R</span>
            </div>
            <span class="font-semibold text-foreground text-sm truncate">RBAS Merchant</span>
          </div>
        </div>
        <div class="flex items-center gap-1 shrink-0">
          <button
            type="button"
            class="p-1.5 rounded-lg text-nav-text hover:text-foreground hover:bg-nav-hover transition-colors"
            :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
            @click="toggle"
          >
            <Sun v-if="isDark" class="w-4 h-4" aria-hidden="true" />
            <Moon v-else class="w-4 h-4" aria-hidden="true" />
          </button>
          <Button
            variant="ghost"
            size="sm"
            class="text-nav-text hover:text-foreground text-xs gap-1.5"
            :disabled="isLoggingOut"
            @click="logout"
          >
            <LogOut class="w-3.5 h-3.5 shrink-0" aria-hidden="true" />
            {{ isLoggingOut ? 'Signing out…' : 'Sign out' }}
          </Button>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 p-4 sm:p-6 lg:p-8">
        <!-- Email verification banner (persists across all pages) -->
        <Transition
          enter-active-class="transition-all duration-300 ease-out"
          enter-from-class="opacity-0 -translate-y-2"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition-all duration-200 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-2"
        >
          <EmailVerificationBanner v-if="needsEmailVerification" :email="auth.user?.email" />
        </Transition>

        <!-- Routed page with fade transition -->
        <RouterView v-slot="{ Component }">
          <Transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
            mode="out-in"
          >
            <component :is="Component" :key="route.path" />
          </Transition>
        </RouterView>
      </main>
    </div>
  </div>
</template>
