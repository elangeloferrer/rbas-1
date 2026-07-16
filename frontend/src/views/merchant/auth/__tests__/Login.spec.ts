import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { toast } from 'vue3-toastify'
import { createMemoryHistory, createRouter } from 'vue-router'
import api from '@/lib/axios'
import Login from '@/views/merchant/auth/Login.vue'

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

const mockRedirectAfterLogin = vi.fn()
vi.mock('@/composables/useAuthRedirect', () => ({
  useAuthRedirect: () => ({ redirectAfterLogin: mockRedirectAfterLogin }),
}))

function makeRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/merchant/login', component: { template: '<div/>' } },
      { path: '/merchant/register', component: { template: '<div/>' } },
      { path: '/merchant/forgot-password', component: { template: '<div/>' } },
      { path: '/merchant/dashboard', component: { template: '<div/>' } },
    ],
  })
}

async function mountLogin() {
  const router = makeRouter()
  await router.push('/merchant/login')
  await router.isReady()
  const pinia = createPinia()
  setActivePinia(pinia)
  const wrapper = mount(Login, {
    global: {
      plugins: [pinia, router],
      stubs: { Sun: true, Moon: true },
    },
  })
  return { wrapper }
}

beforeEach(() => {
  vi.clearAllMocks()
  mockRedirectAfterLogin.mockResolvedValue(undefined)
})

// ── Rendering ──────────────────────────────────────────────────────────────

describe('login.vue — rendering', () => {
  it('renders email and password inputs', async () => {
    const { wrapper } = await mountLogin()
    expect(wrapper.findAll('input')).toHaveLength(2)
  })

  it('submit button shows default label', async () => {
    const { wrapper } = await mountLogin()
    expect(wrapper.find('button[type="submit"]').text()).toBe('Sign in to Merchant Portal')
  })

  it('submit button is enabled on initial render', async () => {
    const { wrapper } = await mountLogin()
    expect(wrapper.find('button[type="submit"]').attributes('disabled')).toBeUndefined()
  })

  it('renders a link to the register page', async () => {
    const { wrapper } = await mountLogin()
    expect(wrapper.find('a[href="/merchant/register"]').exists()).toBe(true)
  })

  it('renders a forgot password link', async () => {
    const { wrapper } = await mountLogin()
    expect(wrapper.find('a[href="/merchant/forgot-password"]').exists()).toBe(true)
  })
})

// ── Validation ─────────────────────────────────────────────────────────────

describe('login.vue — validation', () => {
  it('shows required errors when submitting an empty form', async () => {
    const { wrapper } = await mountLogin()
    await wrapper.find('form').trigger('submit')
    await new Promise((r) => setTimeout(r, 20))
    await flushPromises()

    const text = wrapper.text()
    expect(text).toContain('Email is required')
    expect(text).toContain('Password is required')
  })

  it('shows an error for an invalid email format', async () => {
    const { wrapper } = await mountLogin()
    const inputs = wrapper.findAll('input')
    await inputs[0]!.setValue('not-an-email')
    await inputs[1]!.setValue('Password1!')
    await wrapper.find('form').trigger('submit')
    await new Promise((r) => setTimeout(r, 20))
    await flushPromises()

    expect(wrapper.text()).toContain('Enter a valid email address')
  })

  it('does not call the store action when the form is invalid', async () => {
    const { wrapper } = await mountLogin()
    await wrapper.find('form').trigger('submit')
    await new Promise((r) => setTimeout(r, 20))
    await flushPromises()

    expect(api.post).not.toHaveBeenCalled()
  })
})

// ── Form submission ─────────────────────────────────────────────────────────

describe('login.vue — form submission', () => {
  it('pOSTs to /merchant/login with the correct payload on valid submit', async () => {
    vi.mocked(api.post).mockResolvedValue({})
    vi.mocked(api.get).mockResolvedValue({
      data: {
        data: {
          first_name: 'Jane',
          email: 'jane@merchant.test',
          role: 'merchant',
          is_email_verified: true,
        },
      },
    })

    const { wrapper } = await mountLogin()
    const inputs = wrapper.findAll('input')
    await inputs[0]!.setValue('jane@merchant.test')
    await inputs[1]!.setValue('Password1!')
    await wrapper.find('form').trigger('submit')
    await new Promise((r) => setTimeout(r, 20))
    await flushPromises()

    expect(api.post).toHaveBeenCalledWith('/merchant/login', {
      email: 'jane@merchant.test',
      password: 'Password1!',
    })
  })

  it('shows a success toast and calls redirectAfterLogin on success', async () => {
    vi.mocked(api.post).mockResolvedValue({})
    vi.mocked(api.get).mockResolvedValue({
      data: {
        data: {
          first_name: 'Jane',
          email: 'jane@merchant.test',
          role: 'merchant',
          is_email_verified: true,
        },
      },
    })

    const { wrapper } = await mountLogin()
    const inputs = wrapper.findAll('input')
    await inputs[0]!.setValue('jane@merchant.test')
    await inputs[1]!.setValue('Password1!')
    await wrapper.find('form').trigger('submit')
    await new Promise((r) => setTimeout(r, 20))
    await flushPromises()

    expect(toast.success).toHaveBeenCalledWith('Welcome back!')
    expect(mockRedirectAfterLogin).toHaveBeenCalledOnce()
  })

  it('disables the submit button and shows loading text while request is in flight', async () => {
    let resolvePost!: () => void
    vi.mocked(api.post).mockReturnValue(
      new Promise((resolve) => {
        resolvePost = () => resolve({} as never)
      }),
    )

    const { wrapper } = await mountLogin()
    const inputs = wrapper.findAll('input')
    await inputs[0]!.setValue('jane@merchant.test')
    await inputs[1]!.setValue('Password1!')
    await wrapper.find('form').trigger('submit')
    await new Promise((r) => setTimeout(r, 20))
    await flushPromises() // validation resolves; onSubmit starts; POST is pending

    expect(wrapper.find('button[type="submit"]').text()).toBe('Signing in…')
    expect(wrapper.find('button[type="submit"]').attributes('disabled')).toBeDefined()

    resolvePost()
    await flushPromises()
  })

  it('shows the API error message on a 401 response', async () => {
    vi.mocked(api.post).mockRejectedValue({
      response: {
        status: 401,
        data: { message: 'These credentials do not match our records.' },
      },
    })

    const { wrapper } = await mountLogin()
    const inputs = wrapper.findAll('input')
    await inputs[0]!.setValue('jane@merchant.test')
    await inputs[1]!.setValue('WrongPassword1!')
    await wrapper.find('form').trigger('submit')
    await new Promise((r) => setTimeout(r, 20))
    await flushPromises()

    expect(toast.error).toHaveBeenCalledWith('These credentials do not match our records.')
  })

  it('shows the API error message on a 403 response (inactive account)', async () => {
    vi.mocked(api.post).mockRejectedValue({
      response: {
        status: 403,
        data: { message: 'Your account has been deactivated.' },
      },
    })

    const { wrapper } = await mountLogin()
    const inputs = wrapper.findAll('input')
    await inputs[0]!.setValue('jane@merchant.test')
    await inputs[1]!.setValue('Password1!')
    await wrapper.find('form').trigger('submit')
    await new Promise((r) => setTimeout(r, 20))
    await flushPromises()

    expect(toast.error).toHaveBeenCalledWith('Your account has been deactivated.')
  })

  it('shows a fallback error message when the error has no response body', async () => {
    vi.mocked(api.post).mockRejectedValue(new Error('Network error'))

    const { wrapper } = await mountLogin()
    const inputs = wrapper.findAll('input')
    await inputs[0]!.setValue('jane@merchant.test')
    await inputs[1]!.setValue('Password1!')
    await wrapper.find('form').trigger('submit')
    await new Promise((r) => setTimeout(r, 20))
    await flushPromises()

    expect(toast.error).toHaveBeenCalledWith('Login failed. Please try again.')
  })
})
