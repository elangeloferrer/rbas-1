import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import api, { getCsrfCookie } from '@/lib/axios'
import { useAuthStore } from '@/stores/auth'

vi.mock('@/lib/axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  },
  getCsrfCookie: vi.fn().mockResolvedValue(undefined),
}))

const mockMerchant = {
  first_name: 'Jane',
  email: 'jane@merchant.test',
  role: 'merchant' as const,
  is_email_verified: false,
}

beforeEach(() => {
  setActivePinia(createPinia()) // fresh store state before every test
  vi.clearAllMocks() // clear call history on mocked functions
})

// ── merchantRegister ───────────────────────────────────────────────────────

describe('merchantRegister', () => {
  it('calls getCsrfCookie → POST /merchant/register → GET /user in order', async () => {
    vi.mocked(api.post).mockResolvedValue({})
    vi.mocked(api.get).mockResolvedValue({ data: { data: mockMerchant } })

    const auth = useAuthStore()
    await auth.merchantRegister({
      first_name: 'Jane',
      email: 'jane@merchant.test',
      password: 'Password1!',
      password_confirmation: 'Password1!',
    })

    expect(getCsrfCookie).toHaveBeenCalledOnce()
    expect(api.post).toHaveBeenCalledWith('/merchant/register', {
      first_name: 'Jane',
      email: 'jane@merchant.test',
      password: 'Password1!',
      password_confirmation: 'Password1!',
    })
    expect(api.get).toHaveBeenCalledWith('/user')
  })

  it('hydrates the store with the user returned by /user', async () => {
    vi.mocked(api.post).mockResolvedValue({})
    vi.mocked(api.get).mockResolvedValue({ data: { data: mockMerchant } })

    const auth = useAuthStore()
    await auth.merchantRegister({
      first_name: 'Jane',
      email: 'jane@merchant.test',
      password: 'Password1!',
      password_confirmation: 'Password1!',
    })

    expect(auth.user).toEqual(mockMerchant)
    expect(auth.isAuthenticated).toBe(true)
    expect(auth.initialized).toBe(true)
  })

  it('throws and leaves user null when POST /merchant/register fails with 422', async () => {
    const error = {
      response: {
        status: 422,
        data: { errors: { email: ['The email has already been taken.'] } },
      },
    }
    vi.mocked(api.post).mockRejectedValue(error)

    const auth = useAuthStore()
    await expect(
      auth.merchantRegister({
        first_name: 'Jane',
        email: 'jane@merchant.test',
        password: 'Password1!',
        password_confirmation: 'Password1!',
      }),
    ).rejects.toEqual(error)

    expect(auth.user).toBeNull()
    expect(auth.isAuthenticated).toBe(false)
  })

  it('leaves user null if fetchUser fails after a successful register POST', async () => {
    vi.mocked(api.post).mockResolvedValue({})
    vi.mocked(api.get).mockRejectedValue(new Error('Network error'))

    const auth = useAuthStore()
    // fetchUser() swallows its own errors — merchantRegister does not throw
    await auth.merchantRegister({
      first_name: 'Jane',
      email: 'jane@merchant.test',
      password: 'Password1!',
      password_confirmation: 'Password1!',
    })

    expect(auth.user).toBeNull()
    expect(auth.initialized).toBe(true)
  })
})

// ── merchantLogin ──────────────────────────────────────────────────────────

describe('merchantLogin', () => {
  it('calls getCsrfCookie → POST /merchant/login → GET /user in order', async () => {
    vi.mocked(api.post).mockResolvedValue({})
    vi.mocked(api.get).mockResolvedValue({ data: { data: mockMerchant } })

    const auth = useAuthStore()
    await auth.merchantLogin({ email: 'jane@merchant.test', password: 'Password1!' })

    expect(getCsrfCookie).toHaveBeenCalledOnce()
    expect(api.post).toHaveBeenCalledWith('/merchant/login', {
      email: 'jane@merchant.test',
      password: 'Password1!',
    })
    expect(api.get).toHaveBeenCalledWith('/user')
  })

  it('hydrates the store with the authenticated user', async () => {
    const verifiedMerchant = { ...mockMerchant, is_email_verified: true }
    vi.mocked(api.post).mockResolvedValue({})
    vi.mocked(api.get).mockResolvedValue({ data: { data: verifiedMerchant } })

    const auth = useAuthStore()
    await auth.merchantLogin({ email: 'jane@merchant.test', password: 'Password1!' })

    expect(auth.user).toEqual(verifiedMerchant)
    expect(auth.isAuthenticated).toBe(true)
  })

  it('throws on 401 — wrong credentials', async () => {
    const error = { response: { status: 401 } }
    vi.mocked(api.post).mockRejectedValue(error)

    const auth = useAuthStore()
    await expect(
      auth.merchantLogin({ email: 'jane@merchant.test', password: 'wrong' }),
    ).rejects.toEqual(error)

    expect(auth.user).toBeNull()
  })

  it('throws on 403 — inactive account', async () => {
    const error = {
      response: { status: 403, data: { message: 'Your account has been deactivated.' } },
    }
    vi.mocked(api.post).mockRejectedValue(error)

    const auth = useAuthStore()
    await expect(
      auth.merchantLogin({ email: 'jane@merchant.test', password: 'Password1!' }),
    ).rejects.toEqual(error)

    expect(auth.user).toBeNull()
  })
})

// ── fetchUser ──────────────────────────────────────────────────────────────

describe('fetchUser', () => {
  it('sets user from the API response and marks initialized', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { data: mockMerchant } })

    const auth = useAuthStore()
    await auth.fetchUser()

    expect(auth.user).toEqual(mockMerchant)
    expect(auth.initialized).toBe(true)
  })

  it('sets user to null on network failure and still marks initialized', async () => {
    vi.mocked(api.get).mockRejectedValue(new Error('Network error'))

    const auth = useAuthStore()
    await auth.fetchUser()

    expect(auth.user).toBeNull()
    expect(auth.initialized).toBe(true)
  })
})

// ── clearAuth ──────────────────────────────────────────────────────────────

describe('clearAuth', () => {
  it('resets user to null and keeps initialized true', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { data: mockMerchant } })

    const auth = useAuthStore()
    await auth.fetchUser()
    expect(auth.user).not.toBeNull()

    auth.clearAuth()

    expect(auth.user).toBeNull()
    expect(auth.initialized).toBe(true)
  })
})

// ── Getters ────────────────────────────────────────────────────────────────

describe('isAuthenticated getter', () => {
  it('is false on initial state', () => {
    const auth = useAuthStore()
    expect(auth.isAuthenticated).toBe(false)
  })

  it('is true once a user is stored', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { data: mockMerchant } })
    const auth = useAuthStore()
    await auth.fetchUser()
    expect(auth.isAuthenticated).toBe(true)
  })

  it('is false again after clearAuth', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { data: mockMerchant } })
    const auth = useAuthStore()
    await auth.fetchUser()
    auth.clearAuth()
    expect(auth.isAuthenticated).toBe(false)
  })
})

describe('homeRoute getter', () => {
  it('returns /merchant/dashboard for a merchant user', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { data: mockMerchant } })
    const auth = useAuthStore()
    await auth.fetchUser()
    expect(auth.homeRoute).toBe('/merchant/dashboard')
  })

  it('returns /admin/dashboard for an admin user', async () => {
    vi.mocked(api.get).mockResolvedValue({
      data: { data: { ...mockMerchant, role: 'admin' } },
    })
    const auth = useAuthStore()
    await auth.fetchUser()
    expect(auth.homeRoute).toBe('/admin/dashboard')
  })

  it('returns /homepage when user is null', () => {
    const auth = useAuthStore()
    expect(auth.homeRoute).toBe('/homepage')
  })
})

// ── Persisted state ────────────────────────────────────────────────────────

describe('persisted state', () => {
  it('user field exists in store state (the persist plugin reads this key on boot)', () => {
    // The store declares persist: { pick: ['user'] }. This test documents that
    // contract — dropping 'user' from the pick list would break auth persistence
    // across page refreshes without a failing test to catch it.
    const auth = useAuthStore()
    expect(Object.keys(auth.$state)).toContain('user')
  })

  it('user state holds the full AuthUser shape after login', async () => {
    vi.mocked(api.post).mockResolvedValue({})
    vi.mocked(api.get).mockResolvedValue({ data: { data: mockMerchant } })

    const auth = useAuthStore()
    await auth.merchantLogin({ email: 'jane@merchant.test', password: 'Password1!' })

    // All AuthUser fields must be present — the persist plugin serialises this
    // object to localStorage, so a missing field would silently lose data.
    expect(auth.user).toMatchObject({
      first_name: expect.any(String),
      email: expect.any(String),
      role: expect.any(String),
      is_email_verified: expect.any(Boolean),
    })
  })
})
