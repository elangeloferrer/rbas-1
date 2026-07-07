<script setup lang="ts">
import { ref } from 'vue'
import { toast } from 'vue3-toastify'
import { useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const isLoggingOut = ref(false)

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
    <!-- Sidebar (hidden on mobile, shown on lg+) -->
    <aside class="hidden lg:flex flex-col w-64 bg-emerald-900/80 border-r border-emerald-800 min-h-screen">
      <!-- Logo -->
      <div class="h-16 flex items-center gap-3 px-6 border-b border-emerald-800">
        <div class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center">
          <span class="text-white font-bold text-sm">R</span>
        </div>
        <span class="font-bold text-white">RBAS Merchant</span>
      </div>

      <!-- Nav items -->
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

      <!-- Logout -->
      <div class="p-4 border-t border-emerald-800">
        <div class="flex items-center gap-3 mb-3 px-1">
          <div class="w-8 h-8 rounded-full bg-emerald-700 flex items-center justify-center text-sm font-semibold shrink-0">
            {{ auth.user?.first_name?.charAt(0).toUpperCase() }}
          </div>
          <div class="min-w-0">
            <p class="text-sm font-medium text-white truncate">
              {{ auth.user?.first_name }}
            </p>
            <p class="text-xs text-emerald-400 truncate">
              {{ auth.user?.email }}
            </p>
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

    <!-- Main area -->
    <div class="flex-1 flex flex-col min-h-screen">
      <!-- Mobile top bar -->
      <header class="lg:hidden h-14 flex items-center justify-between px-4 bg-emerald-900/80 border-b border-emerald-800">
        <div class="flex items-center gap-2">
          <div class="w-7 h-7 rounded-lg bg-emerald-600 flex items-center justify-center">
            <span class="text-white font-bold text-xs">R</span>
          </div>
          <span class="font-semibold text-white text-sm">RBAS Merchant</span>
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
      <main class="flex-1 p-6 lg:p-8">
        <div class="mb-8">
          <h1 class="text-2xl font-bold text-white">
            Good day, {{ auth.user?.first_name }} 👋
          </h1>
          <p class="text-emerald-400 mt-1 text-sm">
            Here's your merchant overview
          </p>
        </div>

        <!-- Stats grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
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
              <p class="text-3xl font-bold text-white">
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
          <CardContent class="flex flex-col items-center justify-center py-16 text-center">
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
