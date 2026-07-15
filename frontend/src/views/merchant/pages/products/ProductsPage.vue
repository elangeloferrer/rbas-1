<script setup lang="ts">
import { ShoppingBag } from '@lucide/vue'
import { Card, CardContent } from '@/components/ui/card'
</script>

<template>
  <div>
    <div class="mb-6 lg:mb-8">
      <h1 class="text-xl sm:text-2xl font-bold text-foreground">Products</h1>
      <p class="text-nav-text mt-1 text-sm">Manage your product catalog</p>
    </div>

    <Card class="bg-surface/30 border-surface-border/60 border-dashed rounded-xl">
      <CardContent class="flex flex-col items-center justify-center py-12 lg:py-16 text-center">
        <ShoppingBag class="w-12 h-12 text-nav-disabled mb-4" aria-hidden="true" />
        <h3 class="text-lg font-semibold text-heading-muted mb-2">Hello Product Page</h3>
        <p class="text-sm text-subtle max-w-sm">
          Product management will be available in a future part.
        </p>
      </CardContent>
    </Card>
  </div>
</template>
