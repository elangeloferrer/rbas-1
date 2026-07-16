import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { toast } from 'vue3-toastify'
import { createMemoryHistory, createRouter } from 'vue-router'
import api from '@/lib/axios'
import Register from '@/views/merchant/auth/Register.vue'

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
      { path: '/merchant/register', component: { template: '<div/>' } },
      { path: '/merchant/login', component: { template: '<div/>' } },
      { path: '/merchant/dashboard', component: { template: '<div/>' } },
    ],
  })
}

async function mountRegister() {
  const router = makeRouter()
  await router.push('/merchant/register')
  await router.isReady()
  const pinia = createPinia()
  setActivePinia(pinia)
  const wrapper = mount(Register, {
    global: {
      plugins: [pinia, router],
      stubs: { Sun: true, Moon: true },
    },
  })
  return { wrapper }
}

const validPayload = {
  first_name: 'Jane',
  email: 'jane@merchant.test',
  password: 'Password1!',
  password_confirmation: 'Password1!',
}

async function fillForm(
  wrapper: ReturnType<typeof mount>,
  overrides: Partial<typeof validPayload> = {},
) {
  const vals = { ...validPayload, ...overrides }
  const inputs = wrapper.findAll('input')
  await inputs[0]!.setValue(vals.first_name)
  await inputs[1]!.setValue(vals.email)
  await inputs[2]!.setValue(vals.password)
  await inputs[3]!.setValue(vals.password_confirmation)
}

beforeEach(() => {
  vi.clearAllMocks()
  mockRedirectAfterLogin.mockResolvedValue(undefined)
})

// ── Rendering ──────────────────────────────────────────────────────────────

describe('register.vue — rendering', () => {
  it('renders four form inputs: first_name, email, password, password_confirmation', async () => {
    const { wrapper } = await mountRegister()
    expect(wrapper.findAll('input')).toHaveLength(4)
  })

  it('submit button shows default label', async () => {
    const { wrapper } = await mountRegister()
    expect(wrapper.find('button[type="submit"]').text()).toBe('Create merchant account')
  })

  it('submit button is enabled on initial render', async () => {
    const { wrapper } = await mountRegister()
    expect(wrapper.find('button[type="submit"]').attributes('disabled')).toBeUndefined()
  })

  it('renders a link to the login page', async () => {
    const { wrapper } = await mountRegister()
    expect(wrapper.find('a[href="/merchant/login"]').exists()).toBe(true)
  })
})

// ── Validation ─────────────────────────────────────────────────────────────

describe('register.vue — validation', () => {
  it('shows required field errors when submitting an empty form', async () => {
    const { wrapper } = await mountRegister()
    await wrapper.find('form').trigger('submit')
    await new Promise((r) => setTimeout(r, 20))
    await flushPromises()

    const text = wrapper.text()
    expect(text).toContain('Name must be at least 2 characters')
    expect(text).toContain('Email is required')
    expect(text).toContain('Password must be at least 8 characters')
    expect(text).toContain('Please confirm your password')
  })

  it('shows an error for an invalid email format', async () => {
    const { wrapper } = await mountRegister()
    await fillForm(wrapper, { email: 'not-an-email' })
    await wrapper.find('form').trigger('submit')
    await new Promise((r) => setTimeout(r, 20))
    await flushPromises()

    expect(wrapper.text()).toContain('Enter a valid email address')
  })

  it('shows an error when password lacks an uppercase letter', async () => {
    const { wrapper } = await mountRegister()
    await fillForm(wrapper, { password: 'password1!', password_confirmation: 'password1!' })
    await wrapper.find('form').trigger('submit')
    await new Promise((r) => setTimeout(r, 20))
    await flushPromises()

    expect(wrapper.text()).toContain('Must contain at least one uppercase letter')
  })

  it('shows an error when password lacks a number', async () => {
    const { wrapper } = await mountRegister()
    await fillForm(wrapper, { password: 'Password!', password_confirmation: 'Password!' })
    await wrapper.find('form').trigger('submit')
    await new Promise((r) => setTimeout(r, 20))
    await flushPromises()

    expect(wrapper.text()).toContain('Must contain at least one number')
  })

  it('shows an error when password lacks a special character', async () => {
    const { wrapper } = await mountRegister()
    await fillForm(wrapper, { password: 'Password1', password_confirmation: 'Password1' })
    await wrapper.find('form').trigger('submit')
    await new Promise((r) => setTimeout(r, 20))
    await flushPromises()

    expect(wrapper.text()).toContain('Must contain at least one special character')
  })

  it('shows an error when passwords do not match', async () => {
    const { wrapper } = await mountRegister()
    await fillForm(wrapper, { password_confirmation: 'Different1!' })
    await wrapper.find('form').trigger('submit')
    await new Promise((r) => setTimeout(r, 20))
    await flushPromises()

    expect(wrapper.text()).toContain("Passwords don't match")
  })

  it('does not call the store action when the form is invalid', async () => {
    const { wrapper } = await mountRegister()
    await wrapper.find('form').trigger('submit')
    await new Promise((r) => setTimeout(r, 20))
    await flushPromises()

    expect(api.post).not.toHaveBeenCalled()
  })
})

// ── Form submission ─────────────────────────────────────────────────────────

describe('register.vue — form submission', () => {
  it('pOSTs to /merchant/register with the correct payload on valid submit', async () => {
    vi.mocked(api.post).mockResolvedValue({})
    vi.mocked(api.get).mockResolvedValue({
      data: {
        data: {
          first_name: 'Jane',
          email: 'jane@merchant.test',
          role: 'merchant',
          is_email_verified: false,
        },
      },
    })

    const { wrapper } = await mountRegister()
    await fillForm(wrapper)
    await wrapper.find('form').trigger('submit')
    await new Promise((r) => setTimeout(r, 20))
    await flushPromises()

    expect(api.post).toHaveBeenCalledWith('/merchant/register', validPayload)
  })

  it('shows a success toast and calls redirectAfterLogin on success', async () => {
    vi.mocked(api.post).mockResolvedValue({})
    vi.mocked(api.get).mockResolvedValue({
      data: {
        data: {
          first_name: 'Jane',
          email: 'jane@merchant.test',
          role: 'merchant',
          is_email_verified: false,
        },
      },
    })

    const { wrapper } = await mountRegister()
    await fillForm(wrapper)
    await wrapper.find('form').trigger('submit')
    await new Promise((r) => setTimeout(r, 20))
    await flushPromises()

    expect(toast.success).toHaveBeenCalledWith(
      'Welcome! A verification email has been sent to your inbox.',
    )
    expect(mockRedirectAfterLogin).toHaveBeenCalledOnce()
  })

  it('disables the submit button and shows loading text while the request is in flight', async () => {
    let resolvePost!: () => void
    vi.mocked(api.post).mockReturnValue(
      new Promise((resolve) => {
        resolvePost = () => resolve({} as never)
      }),
    )

    const { wrapper } = await mountRegister()
    await fillForm(wrapper)
    await wrapper.find('form').trigger('submit')
    await new Promise((r) => setTimeout(r, 20))
    await flushPromises() // validation resolves; onSubmit starts; POST is pending

    expect(wrapper.find('button[type="submit"]').text()).toBe('Creating account…')
    expect(wrapper.find('button[type="submit"]').attributes('disabled')).toBeDefined()

    resolvePost()
    await flushPromises()
  })

  it('shows the first field-level error from a 422 response', async () => {
    vi.mocked(api.post).mockRejectedValue({
      response: {
        status: 422,
        data: { errors: { email: ['The email has already been taken.'] } },
      },
    })

    const { wrapper } = await mountRegister()
    await fillForm(wrapper)
    await wrapper.find('form').trigger('submit')
    await new Promise((r) => setTimeout(r, 20))
    await flushPromises()

    expect(toast.error).toHaveBeenCalledWith('The email has already been taken.')
  })

  it('shows the API message on a non-422 error', async () => {
    vi.mocked(api.post).mockRejectedValue({
      response: { status: 500, data: { message: 'Internal server error.' } },
    })

    const { wrapper } = await mountRegister()
    await fillForm(wrapper)
    await wrapper.find('form').trigger('submit')
    await new Promise((r) => setTimeout(r, 20))
    await flushPromises()

    expect(toast.error).toHaveBeenCalledWith('Internal server error.')
  })

  it('shows a fallback error message when the error has no response', async () => {
    vi.mocked(api.post).mockRejectedValue(new Error('Network error'))

    const { wrapper } = await mountRegister()
    await fillForm(wrapper)
    await wrapper.find('form').trigger('submit')
    await new Promise((r) => setTimeout(r, 20))
    await flushPromises()

    expect(toast.error).toHaveBeenCalledWith('Registration failed. Please try again.')
  })
})
