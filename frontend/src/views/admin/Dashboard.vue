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
  { label: 'Total Users', value: '—', icon: '👥' },
  { label: 'Merchants', value: '—', icon: '🏪' },
  { label: 'Orders Today', value: '—', icon: '📦' },
  { label: 'System Health', value: '—', icon: '💚' },
]

const navItems = [
  { label: 'Overview', icon: '📊', active: true },
  { label: 'Users', icon: '👥', active: false },
  { label: 'Merchants', icon: '🏪', active: false },
  { label: 'Orders', icon: '📦', active: false },
  { label: 'Reports', icon: '📈', active: false },
  { label: 'Settings', icon: '⚙️', active: false },
]

async function logout() {
  isLoggingOut.value = true
  try {
    await auth.logout()
    toast.success('Signed out.')
    await router.push('/admin/login')
  }
  catch {
    toast.error('Could not sign out.')
  }
  finally {
    isLoggingOut.value = false
  }
}
</script>

<template>
  <div class="theme-admin min-h-screen bg-slate-950 text-white flex">
    <!-- Sidebar -->
    <aside class="hidden lg:flex flex-col w-60 bg-slate-900 border-r border-slate-800 min-h-screen">
      <div class="h-16 flex items-center gap-3 px-5 border-b border-slate-800">
        <div class="w-7 h-7 rounded-lg bg-slate-600 flex items-center justify-center">
          <span class="text-white font-bold text-xs">R</span>
        </div>
        <div>
          <p class="font-semibold text-white text-sm leading-tight">
            RBAS Admin
          </p>
          <p class="text-slate-500 text-xs">
            Control Panel
          </p>
        </div>
      </div>

      <nav class="flex-1 px-3 py-4 space-y-0.5">
        <button
          v-for="item in navItems"
          :key="item.label"
          class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors"
          :class="item.active
            ? 'bg-slate-700/80 text-white font-medium'
            : 'text-slate-400 hover:text-white hover:bg-slate-800'"
        >
          <span class="text-base">{{ item.icon }}</span>
          {{ item.label }}
        </button>
      </nav>

      <div class="p-3 border-t border-slate-800">
        <div class="flex items-center gap-3 mb-3 px-1">
          <div class="w-8 h-8 rounded-full bg-slate-600 flex items-center justify-center text-sm font-semibold shrink-0">
            {{ auth.user?.first_name?.charAt(0).toUpperCase() }}
          </div>
          <div class="min-w-0">
            <p class="text-sm font-medium text-white truncate">
              {{ auth.user?.first_name }}
            </p>
            <p class="text-xs text-slate-500 truncate">
              Administrator
            </p>
          </div>
        </div>
        <Button
          variant="ghost"
          class="w-full text-slate-500 hover:text-white hover:bg-slate-800 text-xs justify-start h-8"
          :disabled="isLoggingOut"
          @click="logout"
        >
          {{ isLoggingOut ? 'Signing out…' : '← Sign out' }}
        </Button>
      </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col">
      <!-- Mobile header -->
      <header class="lg:hidden h-14 flex items-center justify-between px-4 bg-slate-900 border-b border-slate-800">
        <span class="font-semibold text-white text-sm">RBAS Admin</span>
        <Button
          variant="ghost"
          size="sm"
          class="text-slate-400 hover:text-white text-xs"
          :disabled="isLoggingOut"
          @click="logout"
        >
          Sign out
        </Button>
      </header>

      <main class="flex-1 p-6 lg:p-8">
        <div class="mb-8">
          <h1 class="text-2xl font-bold text-white">
            System Overview
          </h1>
          <p class="text-slate-400 mt-1 text-sm">
            Logged in as
            <span class="text-slate-200 font-medium">{{ auth.user?.email }}</span>
          </p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <Card
            v-for="stat in stats"
            :key="stat.label"
            class="bg-slate-800/60 border-slate-700 text-white rounded-xl"
          >
            <CardHeader class="pb-2">
              <div class="flex items-center justify-between">
                <CardTitle class="text-xs font-medium text-slate-400 uppercase tracking-wider">
                  {{ stat.label }}
                </CardTitle>
                <span class="text-lg">{{ stat.icon }}</span>
              </div>
            </CardHeader>
            <CardContent>
              <p class="text-3xl font-bold text-slate-100">
                {{ stat.value }}
              </p>
              <p class="text-xs text-slate-600 mt-1">
                Wired in Part 13
              </p>
            </CardContent>
          </Card>
        </div>

        <!-- Placeholder -->
        <Card class="bg-slate-800/30 border-slate-700/50 border-dashed rounded-xl">
          <CardContent class="flex flex-col items-center justify-center py-16 text-center">
            <span class="text-4xl mb-4">🛡️</span>
            <h3 class="text-lg font-semibold text-slate-200 mb-2">
              Admin panel ready
            </h3>
            <p class="text-sm text-slate-500 max-w-sm">
              User management, audit logs, and system controls will be connected to the backend API in Part 13.
            </p>
          </CardContent>
        </Card>
      </main>
    </div>
  </div>
</template>
