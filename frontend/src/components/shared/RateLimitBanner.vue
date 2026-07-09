<script setup lang="ts">
import { formatCountdown } from '@/composables/useResendRateLimit'

defineProps<{
  seconds: number
  dismissed: boolean
  variant: 'merchant' | 'customer'
}>()

defineEmits<{
  dismiss: []
}>()
</script>

<template>
  <Transition
    enter-active-class="transition-all duration-300 ease-out"
    enter-from-class="opacity-0 -translate-y-2"
    enter-to-class="opacity-100 translate-y-0"
    leave-active-class="transition-all duration-200 ease-in"
    leave-from-class="opacity-100 translate-y-0"
    leave-to-class="opacity-0 -translate-y-2"
  >
    <div
      v-if="seconds > 0 && !dismissed"
      class="rounded-xl px-4 py-3 flex items-start gap-3 text-left"
      :class="variant === 'merchant'
        ? 'border border-red-500/30 bg-red-500/10'
        : 'border border-red-200 bg-red-50'"
    >
      <svg
        class="w-5 h-5 shrink-0 mt-0.5"
        :class="variant === 'merchant' ? 'text-red-400' : 'text-red-500'"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>

      <div class="flex-1 min-w-0">
        <p
          class="text-sm font-semibold"
          :class="variant === 'merchant' ? 'text-red-300' : 'text-red-700'"
        >
          You've requested too many verification links. To secure your account, please try again in {{ formatCountdown(seconds) }}.
        </p>
        <p
          class="text-xs mt-1 italic"
          :class="variant === 'merchant' ? 'text-red-400' : 'text-red-500'"
        >
          Can't find the email? Check your spam folder.
        </p>
      </div>

      <button
        class="p-1 rounded shrink-0 transition-colors mt-0.5"
        :class="variant === 'merchant' ? 'text-red-600 hover:text-red-400' : 'text-red-400 hover:text-red-600'"
        aria-label="Dismiss"
        @click="$emit('dismiss')"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  </Transition>
</template>
