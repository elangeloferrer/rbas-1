<script setup lang="ts">
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const isLoggedIn = computed(() => auth.isAuthenticated)
</script>

<script lang="ts">
const features = [
  {
    icon: '🛍️',
    title: 'Discover local businesses',
    description: 'Browse merchants near you, read reviews, and find exactly what you need.',
  },
  {
    icon: '⚡',
    title: 'Instant ordering',
    description: 'Place orders in seconds with a streamlined checkout and real-time tracking.',
  },
  {
    icon: '🔒',
    title: 'Secure & private',
    description: 'Your data stays safe. We use industry-standard encryption for every transaction.',
  },
  {
    icon: '💬',
    title: 'Direct messaging',
    description: 'Chat directly with merchants to ask questions or customise your order.',
  },
  {
    icon: '📊',
    title: 'Order history',
    description: 'All your past orders in one place. Reorder with one click.',
  },
  {
    icon: '🌱',
    title: 'Support local',
    description: 'Every purchase on RBAS directly supports independent businesses in your community.',
  },
]
</script>

<template>
  <div class="theme-customer min-h-screen bg-white">
    <!-- Navigation -->
    <nav class="sticky top-0 z-50 bg-white/90 backdrop-blur-sm border-b border-gray-100">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center">
            <span class="text-white font-bold text-sm">R</span>
          </div>
          <span class="font-bold text-gray-900 text-lg">RBAS</span>
        </div>

        <div class="flex items-center gap-3">
          <template v-if="isLoggedIn">
            <RouterLink to="/dashboard">
              <Button variant="default" class="bg-emerald-500 hover:bg-emerald-600 text-white">
                Go to Dashboard
              </Button>
            </RouterLink>
          </template>
          <template v-else>
            <RouterLink to="/login">
              <Button variant="ghost" class="text-gray-600 hover:text-gray-900">
                Sign in
              </Button>
            </RouterLink>
            <RouterLink to="/register">
              <Button class="bg-emerald-500 hover:bg-emerald-600 text-white">
                Get started
              </Button>
            </RouterLink>
          </template>
        </div>
      </div>
    </nav>

    <!-- Hero -->
    <section class="max-w-6xl mx-auto px-4 sm:px-6 pt-20 pb-16 text-center">
      <div class="inline-flex items-center gap-2 bg-emerald-50 text-emerald-700 text-sm font-medium px-4 py-1.5 rounded-full mb-6 border border-emerald-100">
        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
        Now in early access
      </div>

      <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight tracking-tight mb-6">
        The platform built for<br>
        <span class="text-emerald-500">modern commerce</span>
      </h1>

      <p class="text-lg text-gray-500 max-w-2xl mx-auto mb-10 leading-relaxed">
        Connect customers and merchants on a single platform.
        Discover local businesses, manage orders, and grow your reach — all in one place.
      </p>

      <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
        <RouterLink to="/register">
          <Button
            size="lg"
            class="bg-emerald-500 hover:bg-emerald-600 text-white px-8 h-12 text-base font-semibold rounded-xl w-full sm:w-auto"
          >
            Create free account
          </Button>
        </RouterLink>
        <RouterLink to="/merchant/register">
          <Button
            variant="outline"
            size="lg"
            class="border-gray-200 text-gray-700 hover:bg-gray-50 px-8 h-12 text-base font-semibold rounded-xl w-full sm:w-auto"
          >
            List your business →
          </Button>
        </RouterLink>
      </div>
    </section>

    <!-- Features -->
    <section class="bg-gray-50 py-20">
      <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <h2 class="text-2xl sm:text-3xl font-bold text-center text-gray-900 mb-12">
          Everything you need
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="feature in features"
            :key="feature.title"
            class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow"
          >
            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center mb-4">
              <span class="text-xl">{{ feature.icon }}</span>
            </div>
            <h3 class="font-semibold text-gray-900 mb-2">
              {{ feature.title }}
            </h3>
            <p class="text-sm text-gray-500 leading-relaxed">
              {{ feature.description }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="max-w-6xl mx-auto px-4 sm:px-6 py-10 flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-gray-100">
      <div class="flex items-center gap-2">
        <div class="w-6 h-6 rounded bg-emerald-500 flex items-center justify-center">
          <span class="text-white font-bold text-xs">R</span>
        </div>
        <span class="text-sm text-gray-400">© 2026 RBAS. All rights reserved.</span>
      </div>
      <div class="flex gap-6 text-sm text-gray-400">
        <a href="#" class="hover:text-gray-600">Privacy</a>
        <a href="#" class="hover:text-gray-600">Terms</a>
        <RouterLink to="#" class="hover:text-gray-600">
          Extra link
        </RouterLink>
      </div>
    </footer>
  </div>
</template>
