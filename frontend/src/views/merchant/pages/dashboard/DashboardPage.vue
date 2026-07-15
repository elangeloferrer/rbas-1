<script setup lang="ts">
import { LayoutDashboard, PhilippinePeso, ShoppingBag, ShoppingCart, Users } from '@lucide/vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const stats = [
  { label: 'Total Orders', value: '—', icon: ShoppingCart, note: 'Coming soon' },
  { label: 'Total Revenue', value: '—', icon: PhilippinePeso, note: 'Coming soon' },
  { label: 'Active Products', value: '—', icon: ShoppingBag, note: 'Coming soon' },
  { label: 'Customers', value: '—', icon: Users, note: 'Coming soon' },
]
</script>

<template>
  <div>
    <div class="mb-6 lg:mb-8">
      <h1 class="text-xl sm:text-2xl font-bold text-foreground">
        Good day, {{ auth.user?.first_name }} 👋
      </h1>
      <p class="text-nav-text mt-1 text-sm">Here's your merchant overview</p>
    </div>

    <!-- Stats grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-6 lg:mb-8">
      <Card
        v-for="stat in stats"
        :key="stat.label"
        class="bg-surface/50 border-surface-border text-foreground rounded-xl"
      >
        <CardHeader class="pb-2">
          <div class="flex items-center justify-between">
            <CardTitle class="text-xs font-medium text-nav-text uppercase tracking-wider">
              {{ stat.label }}
            </CardTitle>
            <component :is="stat.icon" class="w-5 h-5 text-nav-text" aria-hidden="true" />
          </div>
        </CardHeader>
        <CardContent>
          <p class="text-2xl sm:text-3xl font-bold text-foreground">{{ stat.value }}</p>
          <p class="text-xs text-subtle mt-1">{{ stat.note }}</p>
        </CardContent>
      </Card>
    </div>

    <!-- Placeholder -->
    <Card class="bg-surface/30 border-surface-border/60 border-dashed rounded-xl">
      <CardContent class="flex flex-col items-center justify-center py-12 lg:py-16 text-center">
        <LayoutDashboard class="w-12 h-12 text-nav-disabled mb-4" aria-hidden="true" />
        <h3 class="text-lg font-semibold text-heading-muted mb-2">
          Dashboard coming to life, soon
        </h3>
        <p class="text-sm text-subtle max-w-sm">
          Stats, charts, and order management will be wired to the backend API in the next part.
        </p>
      </CardContent>
    </Card>
  </div>
</template>
