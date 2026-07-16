import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { toast } from 'vue3-toastify'
import { createMemoryHistory, createRouter } from 'vue-router'
import api from '@/lib/axios'
import { useAuthStore } from '@/stores/auth'
import EmailVerifyCallback from '@/views/shared/EmailVerifyCallback.vue'

vi.mock('@/lib/axios', () => ({
  default: { get: vi.fn(), post: vi.fn() },
  getCsrfCookie: vi.fn().mockResolvedValue(undefined),
}))

vi.mock('vue3-toastify', () => ({
  toast: { success: vi.fn(), error: vi.fn() },
}))

vi.mock('@/composables/useColorMode', () => ({
  useColorMode: () => ({ isDark: false, toggle: vi.fn() }),
}))

const mockMerchant = {
  first_name: 'Jane',
  email: 'jane@merchant.test',
  role: 'merchant' as const,
  is_email_verified: true,
}

function makeRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      {
        path: '/merchant/email/verify/:id/:hash',
        component: EmailVerifyCallback,
      },
      { path: '/merchant/dashboard', component: { template: '<div/>' } },
      { path: '/merchant/login', component: { template: '<div/>' } },
    ],
  })
}

const VERIFY_ROUTE = '/merchant/email/verify/42/abc123?expires=9999999999&signature=sig123'

async function mountCallback(options: { authenticated?: boolean } = {}) {
  const router = makeRouter()
  await router.push(VERIFY_ROUTE)
  await router.isReady()

  const pinia = createPinia()
  setActivePinia(pinia)

  if (options.authenticated) {
    const auth = useAuthStore()
    auth.user = mockMerchant
  }

  const wrapper = mount(EmailVerifyCallback, {
    global: {
      plugins: [pinia, router],
      stubs: {
        Sun: true,
        Moon: true,
        CheckCircle2: true,
        XCircle: true,
        Loader2: true,
        Mail: true,
        Clock: true,
        X: true,
      },
    },
  })

  return { wrapper, router }
}

beforeEach(() => {
  vi.clearAllMocks()
})

afterEach(() => {
  vi.useRealTimers()
})

// ── Loading state ──────────────────────────────────────────────────────────

describe('emailVerifyCallback — loading state', () => {
  it('shows the loading state immediately on mount before the API responds', async () => {
    vi.mocked(api.post).mockReturnValue(new Promise(() => {}))

    const { wrapper } = await mountCallback()

    expect(wrapper.text()).toContain('Verifying your email')
  })
})

// ── Success state ──────────────────────────────────────────────────────────

describe('emailVerifyCallback — success state', () => {
  it('shows the success state after the verification API call resolves', async () => {
    vi.mocked(api.post).mockResolvedValue({})

    const { wrapper } = await mountCallback()
    await flushPromises()

    expect(wrapper.text()).toContain('Email verified!')
    expect(wrapper.text()).toContain('Your account is now active.')
  })

  it('shows "Go to Dashboard" when the user is already authenticated', async () => {
    vi.mocked(api.post).mockResolvedValue({})
    vi.mocked(api.get).mockResolvedValue({ data: { data: mockMerchant } })

    const { wrapper } = await mountCallback({ authenticated: true })
    await flushPromises()

    expect(wrapper.text()).toContain('Go to Dashboard')
    expect(wrapper.text()).toContain('Taking you to your dashboard in')
  })

  it('shows "Sign in now" when the user is not authenticated', async () => {
    vi.mocked(api.post).mockResolvedValue({})

    const { wrapper } = await mountCallback()
    await flushPromises()

    expect(wrapper.text()).toContain('Sign in now')
    expect(wrapper.text()).toContain('Redirecting you to sign in in')
  })

  it('posts the correct params extracted from the route to the verify endpoint', async () => {
    vi.mocked(api.post).mockResolvedValue({})

    await mountCallback()
    await flushPromises()

    expect(api.post).toHaveBeenCalledWith(
      '/merchant/email/verify',
      {
        id: '42',
        hash: 'abc123',
        expires: '9999999999',
        signature: 'sig123',
      },
      expect.objectContaining({ signal: expect.any(AbortSignal) }),
    )
  })

  it('calls fetchUser to refresh is_email_verified when the user is authenticated', async () => {
    vi.mocked(api.post).mockResolvedValue({})
    vi.mocked(api.get).mockResolvedValue({ data: { data: mockMerchant } })

    await mountCallback({ authenticated: true })
    await flushPromises()

    expect(api.get).toHaveBeenCalledWith('/user')
  })

  it('starts a countdown after successful verification', async () => {
    vi.useFakeTimers()
    vi.mocked(api.post).mockResolvedValue({})

    const { wrapper } = await mountCallback()
    await flushPromises()

    expect(wrapper.text()).toContain('5')

    vi.advanceTimersByTime(1000)
    await flushPromises()

    expect(wrapper.text()).toContain('4')
  })
})

// ── Error state ────────────────────────────────────────────────────────────

describe('emailVerifyCallback — error state', () => {
  it('shows the error state when the API returns 403', async () => {
    vi.mocked(api.post).mockRejectedValue({
      response: { status: 403, data: { message: 'The link has expired.' } },
    })

    const { wrapper } = await mountCallback()
    await flushPromises()

    expect(wrapper.text()).toContain('Verification failed')
    expect(wrapper.text()).toContain('The link has expired.')
  })

  it('shows a fallback message when the error has no response body', async () => {
    vi.mocked(api.post).mockRejectedValue({
      response: { status: 403, data: {} },
    })

    const { wrapper } = await mountCallback()
    await flushPromises()

    expect(wrapper.text()).toContain('Verification failed. The link may have expired.')
  })

  it('shows "Back to sign in" when unauthenticated and in error state', async () => {
    vi.mocked(api.post).mockRejectedValue({
      response: { status: 403, data: { message: 'Expired.' } },
    })

    const { wrapper } = await mountCallback()
    await flushPromises()

    expect(wrapper.text()).toContain('Back to sign in')
  })

  it('shows "Resend verification email" when authenticated and in error state', async () => {
    vi.mocked(api.post).mockRejectedValue({
      response: { status: 403, data: { message: 'Expired.' } },
    })

    const { wrapper } = await mountCallback({ authenticated: true })
    await flushPromises()

    expect(wrapper.text()).toContain('Resend verification email')
  })
})

// ── Resend ─────────────────────────────────────────────────────────────────

describe('emailVerifyCallback — resend', () => {
  it('pOSTs to /merchant/email/resend and transitions to the "resent" state', async () => {
    // First call (verify) fails; second call (resend) succeeds
    vi.mocked(api.post)
      .mockRejectedValueOnce({ response: { status: 403, data: { message: 'Expired.' } } })
      .mockResolvedValueOnce({})

    const { wrapper } = await mountCallback({ authenticated: true })
    await flushPromises() // verify fails → error state

    const resendBtn = wrapper.findAll('button').find((b) => b.text().includes('Resend'))
    await resendBtn!.trigger('click')
    await flushPromises()

    expect(api.post).toHaveBeenCalledWith('/merchant/email/resend')
    expect(wrapper.text()).toContain('New link sent!')
  })

  it('disables the resend button and shows "Sending…" while the request is in flight', async () => {
    vi.mocked(api.post).mockRejectedValueOnce({
      response: { status: 403, data: { message: 'Expired.' } },
    })

    let resolveResend!: () => void
    vi.mocked(api.post).mockReturnValueOnce(
      new Promise((resolve) => {
        resolveResend = () => resolve({} as never)
      }),
    )

    const { wrapper } = await mountCallback({ authenticated: true })
    await flushPromises() // verify fails

    const resendBtn = wrapper.findAll('button').find((b) => b.text().includes('Resend'))!
    await resendBtn.trigger('click')
    await flushPromises() // resend is in flight

    expect(wrapper.text()).toContain('Sending…')
    expect(resendBtn.attributes('disabled')).toBeDefined()

    resolveResend()
    await flushPromises()
  })

  it('shows the rate limit countdown when resend returns 429', async () => {
    vi.useFakeTimers()

    vi.mocked(api.post)
      .mockRejectedValueOnce({ response: { status: 403, data: { message: 'Expired.' } } })
      .mockRejectedValueOnce({
        response: { status: 429, data: { retry_after: 60 } },
      })

    const { wrapper } = await mountCallback({ authenticated: true })
    await flushPromises()

    const resendBtn = wrapper.findAll('button').find((b) => b.text().includes('Resend'))!
    await resendBtn.trigger('click')
    await flushPromises()

    // The button should show a wait countdown and be disabled
    expect(wrapper.text()).toMatch(/Wait/)
    expect(resendBtn.attributes('disabled')).toBeDefined()
  })

  it('shows a toast error on generic resend failure', async () => {
    vi.mocked(api.post)
      .mockRejectedValueOnce({ response: { status: 403, data: { message: 'Expired.' } } })
      .mockRejectedValueOnce({
        response: { status: 500, data: { message: 'Something went wrong.' } },
      })

    const { wrapper } = await mountCallback({ authenticated: true })
    await flushPromises()

    const resendBtn = wrapper.findAll('button').find((b) => b.text().includes('Resend'))!
    await resendBtn.trigger('click')
    await flushPromises()

    expect(toast.error).toHaveBeenCalledWith('Something went wrong.')
  })
})
