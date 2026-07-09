<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import api from '@/lib/axios'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

type Status = 'loading' | 'success' | 'error'
const status = ref<Status>('loading')
const errorMessage = ref('')
const countdown = ref(5)

// Detect role from path prefix set in the router
const role = route.path.startsWith('/merchant') ? 'merchant' : 'customer'
const isMerchant = role === 'merchant'
const loginPath = isMerchant ? '/merchant/login' : '/login'

let countdownInterval: ReturnType<typeof setInterval> | null = null

// Soft Gate: if already authenticated (merchant auto-login), go to dashboard.
// Otherwise (customer, or merchant who logged out), go to login with ?verified=1.
function goToDestination() {
  if (auth.isAuthenticated) {
    router.push(auth.homeRoute)
  }
  else {
    router.push({ path: loginPath, query: { verified: '1' } })
  }
}

function startCountdown() {
  countdownInterval = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) {
      clearInterval(countdownInterval!)
      goToDestination()
    }
  }, 1000)
}

onMounted(async () => {
  try {
    await api.post(`/${role}/email/verify`, {
      id: route.params.id,
      hash: route.params.hash,
      expires: route.query.expires,
      signature: route.query.signature,
    })
    // Soft Gate: refresh auth so email_verified_at is updated before redirect.
    // The dashboard's verification banner reads this field — refreshing here means
    // the banner is already gone by the time the user lands on the dashboard.
    if (auth.isAuthenticated) {
      await auth.fetchUser()
    }
    status.value = 'success'
    startCountdown()
  }
  catch (e: any) {
    status.value = 'error'
    errorMessage.value = e?.response?.data?.message ?? 'Verification failed. The link may have expired.'
  }
})

onUnmounted(() => {
  if (countdownInterval)
    clearInterval(countdownInterval)
})
</script>

<template>
  <div
    class="flex items-center justify-center p-4" :class="[
      isMerchant
        ? 'theme-merchant min-h-screen bg-linear-to-br from-emerald-950 via-emerald-900 to-green-950'
        : 'theme-customer min-h-screen bg-linear-to-br from-emerald-50 via-white to-teal-50',
    ]"
  >
    <div class="w-full max-w-md text-center">
      <!-- Loading -->
      <template v-if="status === 'loading'">
        <div
          class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-6"
          :class="isMerchant ? 'bg-emerald-800' : 'bg-emerald-100'"
        >
          <svg
            class="w-8 h-8 animate-spin"
            :class="isMerchant ? 'text-emerald-400' : 'text-emerald-500'"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
          </svg>
        </div>
        <h1 class="text-xl font-bold mb-2" :class="isMerchant ? 'text-white' : 'text-gray-900'">
          Verifying your email…
        </h1>
        <p class="text-sm" :class="isMerchant ? 'text-emerald-400' : 'text-gray-500'">
          Please wait while we confirm your email address.
        </p>
      </template>

      <!-- Success -->
      <template v-else-if="status === 'success'">
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
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h1 class="text-xl font-bold mb-2" :class="isMerchant ? 'text-white' : 'text-gray-900'">
          Email verified!
        </h1>
        <p class="text-sm mb-2" :class="isMerchant ? 'text-emerald-300' : 'text-gray-500'">
          Your account is now active.
        </p>
        <p class="text-sm mb-6" :class="isMerchant ? 'text-emerald-400' : 'text-gray-400'">
          {{ auth.isAuthenticated ? 'Taking you to your dashboard in' : 'Redirecting you to sign in in' }}
          <span class="font-bold tabular-nums" :class="isMerchant ? 'text-emerald-200' : 'text-gray-700'">{{ countdown }}</span>
          second{{ countdown !== 1 ? 's' : '' }}…
        </p>
        <Button
          class="font-semibold h-10 px-6 rounded-lg"
          :class="isMerchant
            ? 'bg-emerald-600 hover:bg-emerald-500 text-white border border-emerald-500'
            : 'bg-emerald-500 hover:bg-emerald-600 text-white'"
          @click="goToDestination()"
        >
          {{ auth.isAuthenticated ? 'Go to Dashboard' : 'Sign in now' }}
        </Button>
      </template>

      <!-- Error -->
      <template v-else>
        <div
          class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-6"
          :class="isMerchant ? 'bg-red-900/40 border border-red-800' : 'bg-red-50'"
        >
          <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </div>
        <h1 class="text-xl font-bold mb-2" :class="isMerchant ? 'text-white' : 'text-gray-900'">
          Verification failed
        </h1>
        <p class="text-sm mb-6" :class="isMerchant ? 'text-red-400' : 'text-red-500'">
          {{ errorMessage }}
        </p>
        <div class="flex flex-col gap-3 max-w-xs mx-auto">
          <Button
            class="font-semibold h-10 rounded-lg"
            :class="isMerchant
              ? 'bg-emerald-600 hover:bg-emerald-500 text-white border border-emerald-500'
              : 'bg-emerald-500 hover:bg-emerald-600 text-white'"
            @click="router.push({ path: '/verify-email', query: { role } })"
          >
            Resend verification email
          </Button>
          <Button
            variant="ghost"
            class="h-10"
            :class="isMerchant ? 'text-emerald-400 hover:text-white hover:bg-emerald-800' : 'text-gray-500 hover:text-gray-700'"
            @click="router.push(loginPath)"
          >
            Back to sign in
          </Button>
        </div>
      </template>
    </div>
  </div>
</template>
