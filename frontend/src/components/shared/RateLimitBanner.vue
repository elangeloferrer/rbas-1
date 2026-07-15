<script setup lang="ts">
import { Clock, X } from '@lucide/vue'
import { formatCountdown } from '@/composables/useResendRateLimit'

withDefaults(
  defineProps<{
    seconds: number
    dismissed: boolean
    title?: string
    hint?: string
  }>(),
  {
    title: 'Too many requests',
  },
)

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
      class="rounded-xl px-4 py-3 flex items-start gap-3 text-left border border-danger-border bg-danger-surface"
    >
      <Clock class="w-5 h-5 shrink-0 mt-0.5 text-danger-muted-foreground" aria-hidden="true" />

      <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-danger-foreground">
          {{ title }} — please wait {{ formatCountdown(seconds) }} before trying again.
        </p>
        <p v-if="hint" class="text-xs mt-1 italic text-danger-muted-foreground">
          {{ hint }}
        </p>
      </div>

      <button
        class="p-1 rounded shrink-0 transition-colors mt-0.5 text-danger-dismiss hover:text-danger-dismiss-hover"
        aria-label="Dismiss"
        @click="$emit('dismiss')"
      >
        <X class="w-4 h-4" aria-hidden="true" />
      </button>
    </div>
  </Transition>
</template>
