import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { toast } from 'vue3-toastify'
import EmailVerificationBanner from '@/components/merchant/EmailVerificationBanner.vue'
import api from '@/lib/axios'

vi.mock('@/lib/axios', () => ({
  default: { get: vi.fn(), post: vi.fn() },
  getCsrfCookie: vi.fn().mockResolvedValue(undefined),
}))

vi.mock('vue3-toastify', () => ({
  toast: { success: vi.fn(), error: vi.fn() },
}))

function mountBanner(email = 'jane@merchant.test') {
  return mount(EmailVerificationBanner, {
    props: { email },
    global: {
      stubs: {
        AlertTriangle: true,
        X: true,
        Clock: true,
      },
    },
  })
}

beforeEach(() => {
  vi.clearAllMocks()
})

afterEach(() => {
  vi.useRealTimers()
})

// ── Rendering ──────────────────────────────────────────────────────────────

describe('emailVerificationBanner — rendering', () => {
  it('renders the banner with the provided email address', () => {
    const wrapper = mountBanner()
    expect(wrapper.text()).toContain('jane@merchant.test')
  })

  it('resend button is enabled on initial render', () => {
    const wrapper = mountBanner()
    const resendBtn = wrapper.find('button:not([aria-label="Dismiss"])')
    expect(resendBtn.attributes('disabled')).toBeUndefined()
    expect(resendBtn.text()).toBe('Resend email')
  })

  it('renders the dismiss button', () => {
    const wrapper = mountBanner()
    expect(wrapper.find('button[aria-label="Dismiss"]').exists()).toBe(true)
  })
})

// ── Resend ─────────────────────────────────────────────────────────────────

describe('emailVerificationBanner — resend', () => {
  it('calls POST /merchant/email/resend when the resend button is clicked', async () => {
    vi.mocked(api.post).mockResolvedValue({})

    const wrapper = mountBanner()
    await wrapper.find('button:not([aria-label="Dismiss"])').trigger('click')
    await flushPromises()

    expect(api.post).toHaveBeenCalledWith('/merchant/email/resend')
  })

  it('shows a success toast after a successful resend', async () => {
    vi.mocked(api.post).mockResolvedValue({})

    const wrapper = mountBanner()
    await wrapper.find('button:not([aria-label="Dismiss"])').trigger('click')
    await flushPromises()

    expect(toast.success).toHaveBeenCalledWith('Verification email resent — check your inbox.')
  })

  it('disables the button and shows "Sending…" while the request is in flight', async () => {
    let resolvePost!: () => void
    vi.mocked(api.post).mockReturnValue(
      new Promise((resolve) => {
        resolvePost = () => resolve({} as never)
      }),
    )

    const wrapper = mountBanner()
    const resendBtn = wrapper.find('button:not([aria-label="Dismiss"])')
    await resendBtn.trigger('click')
    await flushPromises() // POST is pending

    expect(resendBtn.text()).toBe('Sending…')
    expect(resendBtn.attributes('disabled')).toBeDefined()

    resolvePost()
    await flushPromises()
  })
})

// ── Rate limiting ──────────────────────────────────────────────────────────

describe('emailVerificationBanner — rate limiting', () => {
  it('disables the resend button and shows a countdown on a 429 response', async () => {
    vi.useFakeTimers()
    vi.mocked(api.post).mockRejectedValue({
      response: { status: 429, data: { retry_after: 60 } },
    })

    const wrapper = mountBanner()
    await wrapper.find('button:not([aria-label="Dismiss"])').trigger('click')
    await flushPromises()

    const resendBtn = wrapper.find('button:not([aria-label="Dismiss"])')
    expect(resendBtn.attributes('disabled')).toBeDefined()
    expect(resendBtn.text()).toMatch(/Wait/)
  })

  it('re-enables the resend button after the rate limit countdown expires', async () => {
    vi.useFakeTimers()
    vi.mocked(api.post).mockRejectedValue({
      response: { status: 429, data: { retry_after: 2 } },
    })

    const wrapper = mountBanner()
    await wrapper.find('button:not([aria-label="Dismiss"])').trigger('click')
    await flushPromises()

    vi.advanceTimersByTime(2000)
    await flushPromises()

    const resendBtn = wrapper.find('button:not([aria-label="Dismiss"])')
    expect(resendBtn.attributes('disabled')).toBeUndefined()
    expect(resendBtn.text()).toBe('Resend email')
  })

  it('shows a generic toast error on non-429 failure', async () => {
    vi.mocked(api.post).mockRejectedValue({
      response: { status: 500 },
    })

    const wrapper = mountBanner()
    await wrapper.find('button:not([aria-label="Dismiss"])').trigger('click')
    await flushPromises()

    expect(toast.error).toHaveBeenCalledWith('Could not resend. Please try again.')
  })
})

// ── Dismiss ────────────────────────────────────────────────────────────────

describe('emailVerificationBanner — dismiss', () => {
  it('hides the verification banner when the dismiss button is clicked', async () => {
    const wrapper = mountBanner()

    expect(wrapper.find('[class*="rounded-xl"]').exists()).toBe(true)

    await wrapper.find('button[aria-label="Dismiss"]').trigger('click')
    await flushPromises()

    // After dismissing: the main banner content should no longer be visible
    expect(wrapper.text()).not.toContain('Verify your email to unlock all features')
  })
})
