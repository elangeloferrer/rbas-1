<script setup lang="ts">
import { ref } from 'vue'
import { toast } from 'vue3-toastify'
import { useRoute, useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import api from '@/lib/axios'
import { useResendRateLimit } from '@/composables/useResendRateLimit'
import RateLimitBanner from '@/components/shared/RateLimitBanner.vue'

const route = useRoute()
const router = useRouter()

const email = (route.query.email as string) ?? ''
const role = (route.query.role as string) === 'merchant' ? 'merchant' : 'customer'
const loginPath = role === 'merchant' ? '/merchant/login' : '/login'
const isMerchant = role === 'merchant'

const isResending = ref(false)
const rateLimitDismissed = ref(false)

const { rateLimitSeconds, startCountdown } = useResendRateLimit()

async function resend() {
  isResending.value = true
  try {
    await api.post(`/${role}/email/resend`)
    toast.success('Verification email resent — check your inbox.')
  }
  catch (error: any) {
    if (error.response?.status === 429) {
      rateLimitDismissed.value = false
      startCountdown(error.response.data.retry_after)
    }
    else {
      toast.error('Could not resend the email. Please try again.')
    }
  }
  finally {
    isResending.value = false
  }
}
</script>

<template>
  <div
    class="flex items-center justify-center p-4" :class="[
      isMerchant
        ? 'theme-merchant min-h-screen bg-gradient-to-br from-emerald-950 via-emerald-900 to-green-950'
        : 'theme-customer min-h-screen bg-gradient-to-br from-emerald-50 via-white to-teal-50',
    ]"
  >
    <div class="w-full max-w-md text-center">
      <!-- Envelope icon -->
      <div
        class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-6"
        :class="isMerchant ? 'bg-emerald-700 border border-emerald-600' : 'bg-emerald-100'"
      >
        <svg
          class="w-8 h-8"
          :class="isMerchant ? 'text-emerald-300' : 'text-emerald-600'"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
          />
        </svg>
      </div>

      <h1
        class="text-2xl font-bold mb-2"
        :class="isMerchant ? 'text-white' : 'text-gray-900'"
      >
        Check your inbox
      </h1>

      <p class="text-sm mb-1" :class="isMerchant ? 'text-emerald-300' : 'text-gray-500'">
        We sent a verification link to
      </p>
      <p
        class="font-semibold text-base mb-6"
        :class="isMerchant ? 'text-white' : 'text-gray-800'"
      >
        {{ email || 'your email address' }}
      </p>

      <p
        class="text-sm mb-8 leading-relaxed"
        :class="isMerchant ? 'text-emerald-400' : 'text-gray-500'"
      >
        Click the link in that email to activate your account. The link expires in
        <strong :class="isMerchant ? 'text-emerald-300' : 'text-gray-700'">24 hours</strong>.
      </p>

      <!-- Rate limit banner -->
      <RateLimitBanner
        class="mb-4"
        :variant="isMerchant ? 'merchant' : 'customer'"
        :seconds="rateLimitSeconds"
        :dismissed="rateLimitDismissed"
        @dismiss="rateLimitDismissed = true"
      />

      <div class="flex flex-col gap-3">
        <Button
          class="w-full font-semibold h-10 rounded-lg transition-colors"
          :class="isMerchant
            ? 'bg-emerald-600 hover:bg-emerald-500 text-white border border-emerald-500'
            : 'bg-emerald-500 hover:bg-emerald-600 text-white'"
          :disabled="isResending || rateLimitSeconds > 0"
          @click="resend"
        >
          {{ isResending ? 'Sending…' : rateLimitSeconds > 0 ? 'Rate limited' : 'Resend verification email' }}
        </Button>

        <Button
          variant="ghost"
          class="w-full h-10"
          :class="isMerchant
            ? 'text-emerald-400 hover:text-white hover:bg-emerald-800'
            : 'text-gray-500 hover:text-gray-700'"
          @click="router.push(loginPath)"
        >
          Back to sign in
        </Button>
      </div>

      <p class="text-xs mt-6" :class="isMerchant ? 'text-emerald-600' : 'text-gray-400'">
        Didn't receive it? Check your spam folder or use the resend button above.
      </p>
    </div>
  </div>
</template>
