<script setup lang="ts">
import { CheckCircle2, Loader2, Mail, Moon, Sun, XCircle } from '@lucide/vue'
import { onMounted, onUnmounted, ref } from 'vue'
import { toast } from 'vue3-toastify'
import { useRoute, useRouter } from 'vue-router'
import RateLimitBanner from '@/components/shared/RateLimitBanner.vue'
import { Button } from '@/components/ui/button'
import { useColorMode } from '@/composables/useColorMode'
import { formatCountdown, useResendRateLimit } from '@/composables/useResendRateLimit'
import api from '@/lib/axios'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const { isDark, toggle } = useColorMode()

type Status = 'loading' | 'success' | 'error' | 'resent'
const status = ref<Status>('loading')
const errorMessage = ref('')
const countdown = ref(5)
const isResending = ref(false)
const rateLimitDismissed = ref(false)

// Detect role from path prefix set in the router
const role = route.path.startsWith('/merchant') ? 'merchant' : 'customer'
const isMerchant = role === 'merchant'
const loginPath = isMerchant ? '/merchant/login' : '/login'

const { rateLimitSeconds, startCountdown: startRateLimitCountdown } = useResendRateLimit()

let redirectInterval: ReturnType<typeof setInterval> | null = null
let verifyController: AbortController | null = null

// Soft Gate: if already authenticated (merchant auto-login), go to dashboard.
// Otherwise (customer, or merchant who logged out), go to login with ?verified=1.
function goToDestination() {
  if (auth.isAuthenticated) {
    router.push(auth.homeRoute)
  } else {
    router.push({ path: loginPath, query: { verified: '1' } })
  }
}

function startRedirectCountdown() {
  redirectInterval = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) {
      clearInterval(redirectInterval!)
      goToDestination()
    }
  }, 1000)
}

async function resend() {
  isResending.value = true
  try {
    await api.post(`/${role}/email/resend`)
    status.value = 'resent'
    rateLimitDismissed.value = false
  } catch (error: any) {
    if (error.response?.status === 429) {
      rateLimitDismissed.value = false
      startRateLimitCountdown(error.response.data.retry_after)
    } else {
      toast.error(error.response?.data?.message ?? 'Failed to send. Please try again.')
    }
  } finally {
    isResending.value = false
  }
}

onMounted(async () => {
  // AbortController lets us cancel the in-flight fetch — either after 15 s
  // (timeout) or immediately when the component unmounts mid-request.
  verifyController = new AbortController()
  const timeoutId = setTimeout(() => verifyController!.abort(), 15000)

  try {
    await api.post(
      `/${role}/email/verify`,
      {
        id: route.params.id,
        hash: route.params.hash,
        expires: route.query.expires,
        signature: route.query.signature,
      },
      { signal: verifyController.signal },
    )
    clearTimeout(timeoutId)
    // Soft Gate: refresh auth so email_verified_at is updated before redirect.
    // The dashboard's verification banner reads this field — refreshing here means
    // the banner is already gone by the time the user lands on the dashboard.
    if (auth.isAuthenticated) {
      try {
        await auth.fetchUser()
      } catch {
        // best-effort refresh — verification already succeeded
      }
    }
    status.value = 'success'
    startRedirectCountdown()
  } catch (e: any) {
    clearTimeout(timeoutId)
    status.value = 'error'
    errorMessage.value =
      e?.code === 'ERR_CANCELED'
        ? 'Verification timed out. Please check your connection and try again.'
        : (e?.response?.data?.message ?? 'Verification failed. The link may have expired.')
  }
})

onUnmounted(() => {
  if (redirectInterval) clearInterval(redirectInterval)
  verifyController?.abort()
})
</script>

<template>
  <div
    class="relative min-h-screen flex items-center justify-center p-4 bg-linear-to-br from-auth-bg-from via-auth-bg-via to-auth-bg-to"
  >
    <button
      type="button"
      class="absolute top-4 right-4 p-2 rounded-lg text-subtle hover:text-foreground hover:bg-nav-hover transition-colors"
      :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
      @click="toggle"
    >
      <Sun v-if="isDark" class="w-5 h-5" aria-hidden="true" />
      <Moon v-else class="w-5 h-5" aria-hidden="true" />
    </button>

    <div class="w-full" aria-live="polite" aria-atomic="true">
      <Transition
        enter-active-class="transition-opacity duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
        mode="out-in"
      >
        <div :key="status" class="w-full max-w-md mx-auto text-center">
          <!-- Loading -->
          <template v-if="status === 'loading'">
            <div
              class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-6 bg-nav-hover border border-icon-surface-border"
            >
              <Loader2 class="w-8 h-8 text-icon-color animate-spin" aria-hidden="true" />
            </div>
            <h1 class="text-2xl font-bold text-foreground mb-2">Verifying your email…</h1>
            <p class="text-sm text-subtle">Please wait while we confirm your email address.</p>
          </template>

          <!-- Success -->
          <template v-else-if="status === 'success'">
            <div
              class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-6 bg-success-surface border border-success-border"
            >
              <CheckCircle2 class="w-8 h-8 text-success-icon" aria-hidden="true" />
            </div>
            <h1 class="text-2xl font-bold text-foreground mb-2">Email verified!</h1>
            <p class="text-sm text-subtle mb-2">Your account is now active.</p>
            <p class="text-sm text-subtle mb-8">
              {{
                auth.isAuthenticated
                  ? 'Taking you to your dashboard in'
                  : 'Redirecting you to sign in in'
              }}
              <span class="font-bold tabular-nums text-heading-muted">{{ countdown }}</span>
              second{{ countdown !== 1 ? 's' : '' }}…
            </p>
            <div class="flex flex-col gap-3 max-w-xs mx-auto">
              <Button
                class="w-full font-semibold h-10 rounded-lg bg-brand text-brand-foreground hover:bg-brand/90 border-0 shadow-none"
                @click="goToDestination()"
              >
                {{ auth.isAuthenticated ? 'Go to Dashboard' : 'Sign in now' }}
              </Button>
            </div>
          </template>

          <!-- Error -->
          <template v-else-if="status === 'error'">
            <div
              class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-6 bg-danger-surface border border-danger-border"
            >
              <XCircle class="w-8 h-8 text-destructive" aria-hidden="true" />
            </div>
            <h1 class="text-2xl font-bold text-foreground mb-2">Verification failed</h1>
            <p class="text-sm text-destructive mb-6">{{ errorMessage }}</p>

            <!-- Authenticated: can resend directly -->
            <template v-if="auth.isAuthenticated">
              <RateLimitBanner
                class="mb-4 text-left"
                :seconds="rateLimitSeconds"
                :dismissed="rateLimitDismissed"
                title="Too many requests."
                hint="Can't find the email? Check your spam folder."
                @dismiss="rateLimitDismissed = true"
              />
              <div class="flex flex-col gap-3 max-w-xs mx-auto">
                <Button
                  class="w-full font-semibold h-10 rounded-lg bg-brand text-brand-foreground hover:bg-brand/90 border-0 shadow-none"
                  :disabled="isResending || rateLimitSeconds > 0"
                  @click="resend"
                >
                  {{
                    isResending
                      ? 'Sending…'
                      : rateLimitSeconds > 0
                        ? `Wait ${formatCountdown(rateLimitSeconds)}`
                        : 'Resend verification email'
                  }}
                </Button>
                <Button
                  variant="ghost"
                  class="w-full h-10 text-nav-text hover:text-foreground hover:bg-nav-hover/60"
                  @click="goToDestination"
                >
                  Go to dashboard
                </Button>
              </div>
            </template>

            <!-- Not authenticated: direct them to sign in -->
            <template v-else>
              <p class="text-sm text-subtle mb-6">
                Sign in and we'll show you how to request a new verification link from your account.
              </p>
              <div class="flex flex-col gap-3 max-w-xs mx-auto">
                <Button
                  class="w-full font-semibold h-10 rounded-lg bg-brand text-brand-foreground hover:bg-brand/90 border-0 shadow-none"
                  @click="router.push(loginPath)"
                >
                  Back to sign in
                </Button>
              </div>
            </template>
          </template>

          <!-- Resent -->
          <template v-else>
            <div
              class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-6 bg-brand/10 border border-brand/20"
            >
              <Mail class="w-8 h-8 text-brand" aria-hidden="true" />
            </div>
            <h1 class="text-2xl font-bold text-foreground mb-2">New link sent!</h1>
            <p class="text-sm text-subtle mb-2">
              We've sent a fresh verification link to your email.
            </p>
            <p class="text-xs text-subtle mb-8">Can't find it? Check your spam folder.</p>

            <RateLimitBanner
              class="mb-4 text-left"
              :seconds="rateLimitSeconds"
              :dismissed="rateLimitDismissed"
              title="Too many requests."
              hint="Can't find the email? Check your spam folder."
              @dismiss="rateLimitDismissed = true"
            />

            <div class="flex flex-col gap-3 max-w-xs mx-auto">
              <Button
                variant="outline"
                class="w-full font-semibold h-10 rounded-lg border-surface-border text-nav-text hover:text-foreground"
                :disabled="isResending || rateLimitSeconds > 0"
                @click="resend"
              >
                {{
                  isResending
                    ? 'Sending…'
                    : rateLimitSeconds > 0
                      ? `Wait ${formatCountdown(rateLimitSeconds)}`
                      : 'Resend again'
                }}
              </Button>
              <Button
                variant="ghost"
                class="w-full h-10 text-nav-text hover:text-foreground hover:bg-nav-hover/60"
                @click="auth.isAuthenticated ? goToDestination() : router.push(loginPath)"
              >
                {{ auth.isAuthenticated ? 'Go to dashboard' : 'Back to sign in' }}
              </Button>
            </div>
          </template>
        </div>
      </Transition>
    </div>
  </div>
</template>
