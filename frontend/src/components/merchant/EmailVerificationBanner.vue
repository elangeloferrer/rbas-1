<script setup lang="ts">
import { AlertTriangle, X } from '@lucide/vue'
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
  } catch (error: any) {
    if (error.response?.status === 429) {
      rateLimitDismissed.value = false
      startCountdown(error.response.data.retry_after)
    } else {
      toast.error('Could not resend. Please try again.')
    }
  } finally {
    isResending.value = false
  }
}
</script>

<template>
  <div v-if="!dismissed || (rateLimitSeconds > 0 && !rateLimitDismissed)" class="mb-6 space-y-3">
    <!-- Verification banner -->
    <div
      v-if="!dismissed"
      class="rounded-xl border border-warning-border bg-warning-surface px-4 py-3"
    >
      <div class="flex items-start gap-3">
        <AlertTriangle class="w-5 h-5 text-warning-icon shrink-0 mt-0.5" aria-hidden="true" />

        <!-- On mobile: text stacked above full-width button.
             On desktop: text and button sit side-by-side in one row. -->
        <div class="flex-1 min-w-0 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-warning-foreground">
              Verify your email to unlock all features
            </p>
            <p class="text-xs text-warning-muted-foreground mt-0.5 truncate">
              We sent a link to
              <span class="font-semibold text-warning-foreground">{{ email }}</span>
            </p>
          </div>

          <Button
            variant="outline"
            class="w-full sm:w-auto h-10 sm:h-8 text-sm sm:text-xs px-4 sm:px-3 shrink-0 border-warning-border text-warning-icon hover:bg-warning-surface hover:text-warning-foreground hover:border-warning-icon"
            :disabled="isResending || rateLimitSeconds > 0"
            @click="resend"
          >
            {{
              isResending
                ? 'Sending…'
                : rateLimitSeconds > 0
                  ? `Wait ${formatCountdown(rateLimitSeconds)}`
                  : 'Resend email'
            }}
          </Button>
        </div>

        <button
          class="text-warning-dismiss hover:text-warning-dismiss-hover transition-colors p-1 rounded shrink-0"
          aria-label="Dismiss"
          @click="dismissed = true"
        >
          <X class="w-4 h-4" aria-hidden="true" />
        </button>
      </div>
    </div>

    <!-- Rate limit banner -->
    <RateLimitBanner
      :seconds="rateLimitSeconds"
      :dismissed="rateLimitDismissed"
      title="You've requested too many verification links."
      hint="Can't find the email? Check your spam folder."
      @dismiss="rateLimitDismissed = true"
    />
  </div>
</template>
