import type { Page } from '@playwright/test'

export interface AuthUser {
  id: number
  first_name: string
  email: string
  role: 'merchant' | 'customer' | 'admin'
  is_email_verified: boolean
}

export const MERCHANT_USER: AuthUser = {
  id: 1,
  first_name: 'Jane',
  email: 'jane@business.com',
  role: 'merchant',
  is_email_verified: true,
}

export const UNVERIFIED_MERCHANT_USER: AuthUser = {
  ...MERCHANT_USER,
  is_email_verified: false,
}

/**
 * Seeds Pinia's persisted auth state before the page's scripts load.
 *
 * pinia-plugin-persistedstate writes the 'auth' store under the key 'auth'
 * in localStorage. Seeding this before navigation means the store hydrates
 * with the user on first read — avoiding any real /api/user call for init.
 *
 * MUST be called before page.goto().
 */
export async function seedAuthStorage(page: Page, user: AuthUser = MERCHANT_USER): Promise<void> {
  await page.addInitScript((u) => {
    localStorage.setItem('auth', JSON.stringify({ user: u }))
  }, user)
}

export async function interceptCsrf(page: Page): Promise<void> {
  await page.route('**/sanctum/csrf-cookie', (route) => route.fulfill({ status: 204, body: '' }))
}

export async function interceptMerchantLogin(
  page: Page,
  opts: { success?: boolean; status?: number; message?: string } = {},
): Promise<void> {
  const {
    success = true,
    status = 422,
    message = 'These credentials do not match our records.',
  } = opts
  await page.route('**/api/merchant/login', (route) =>
    route.fulfill(
      success
        ? { status: 200, contentType: 'application/json', body: JSON.stringify({ message: 'OK' }) }
        : { status, contentType: 'application/json', body: JSON.stringify({ message }) },
    ),
  )
}

export async function interceptMerchantRegister(
  page: Page,
  opts: {
    success?: boolean
    status?: number
    message?: string
    errors?: Record<string, string[]>
  } = {},
): Promise<void> {
  const { success = true, status = 422, message = 'Registration failed.', errors } = opts
  await page.route('**/api/merchant/register', (route) =>
    route.fulfill(
      success
        ? {
            status: 201,
            contentType: 'application/json',
            body: JSON.stringify({ message: 'OK' }),
          }
        : { status, contentType: 'application/json', body: JSON.stringify({ message, errors }) },
    ),
  )
}

export async function interceptFetchUser(
  page: Page,
  opts: { user?: AuthUser | null; status?: number } = {},
): Promise<void> {
  const { user = MERCHANT_USER, status = 200 } = opts
  await page.route('**/api/user', (route) =>
    route.fulfill(
      status === 200
        ? {
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ data: user }),
          }
        : {
            status,
            contentType: 'application/json',
            body: JSON.stringify({ message: 'Unauthenticated.' }),
          },
    ),
  )
}

export async function interceptLogout(page: Page, opts: { status?: number } = {}): Promise<void> {
  const { status = 200 } = opts
  await page.route('**/api/logout', (route) =>
    route.fulfill({
      status,
      contentType: 'application/json',
      body: JSON.stringify({ message: status === 200 ? 'OK' : 'Unauthenticated.' }),
    }),
  )
}

export async function interceptEmailVerify(
  page: Page,
  opts: { success?: boolean; status?: number; message?: string } = {},
): Promise<void> {
  const { success = true, status = 422, message = 'This verification link has expired.' } = opts
  await page.route('**/api/merchant/email/verify', (route) =>
    route.fulfill(
      success
        ? {
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ message: 'Email verified.' }),
          }
        : { status, contentType: 'application/json', body: JSON.stringify({ message }) },
    ),
  )
}

export async function interceptEmailResend(
  page: Page,
  opts: { success?: boolean; status?: number; retryAfter?: number } = {},
): Promise<void> {
  const { success = true, status = 429, retryAfter = 60 } = opts
  await page.route('**/api/merchant/email/resend', (route) =>
    route.fulfill(
      success
        ? {
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ message: 'OK' }),
          }
        : {
            status,
            contentType: 'application/json',
            body: JSON.stringify({ message: 'Too Many Requests.', retry_after: retryAfter }),
          },
    ),
  )
}

/**
 * Convenience wrapper: seeds localStorage AND mocks /api/user so the router
 * guard's fetchUser() call succeeds during initialization.
 *
 * MUST be called before page.goto().
 */
export async function setupAuthenticatedMerchant(
  page: Page,
  user: AuthUser = MERCHANT_USER,
): Promise<void> {
  await seedAuthStorage(page, user)
  await interceptFetchUser(page, { user })
}
