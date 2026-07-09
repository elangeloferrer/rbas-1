<script setup lang="ts">
import { ref } from 'vue'
import { toast } from 'vue3-toastify'
import RateLimitBanner from '@/components/shared/RateLimitBanner.vue'
import { Button } from '@/components/ui/button'
import { formatCountdown, useResendRateLimit } from '@/composables/useResendRateLimit'
import api from '@/lib/axios'

defineProps<{
  email: string | undefined
}>()

const dismissed = ref(false)
const rateLimitDismissed = ref(false)
const isResending = ref(false)

const { rateLimitSeconds, startCountdown } = useResendRateLimit()

async function resend() {
  isResending.value = true
  try {
    await api.post('/merchant/email/resend')
    toast.success('Verification email resent — check your inbox.')
  }
  catch (error: any) {
    if (error.response?.status === 429) {
      rateLimitDismissed.value = false
      startCountdown(error.response.data.retry_after)
    }
    else {
      toast.error('Could not resend. Please try again.')
    }
  }
  finally {
    isResending.value = false
  }
}
</script>

<template>
  <div v-if="!dismissed" class="mb-6 space-y-3">
    <!-- Amber verification banner -->
    <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3">
      <div class="flex items-start gap-3">
        <svg class="w-5 h-5 text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>

        <!-- On mobile: text stacked above full-width button (flex-col).
             On desktop: text and button sit side-by-side in one row (sm:flex-row). -->
        <div class="flex-1 min-w-0 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-amber-300">
              Verify your email to unlock all features
            </p>
            <p class="text-xs text-amber-500 mt-0.5 truncate">
              We sent a link to <span class="font-semibold text-amber-400">{{ email }}</span>
            </p>
          </div>

          <Button
            variant="outline"
            class="w-full sm:w-auto h-10 sm:h-8 text-sm sm:text-xs px-4 sm:px-3 shrink-0 border-amber-500/40 text-amber-400 hover:bg-amber-500/10 hover:text-amber-300 hover:border-amber-400"
            :disabled="isResending || rateLimitSeconds > 0"
            @click="resend"
          >
            {{ isResending ? 'Sending…' : rateLimitSeconds > 0 ? `Wait ${formatCountdown(rateLimitSeconds)}` : 'Resend email' }}
          </Button>
        </div>

        <button
          class="text-amber-600 hover:text-amber-400 transition-colors p-1 rounded shrink-0"
          aria-label="Dismiss"
          @click="dismissed = true"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Rate limit banner -->
    <RateLimitBanner
      variant="merchant"
      :seconds="rateLimitSeconds"
      :dismissed="rateLimitDismissed"
      @dismiss="rateLimitDismissed = true"
    />
  </div>
</template>
