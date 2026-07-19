import type { AuthUser } from './helpers/auth'
import { expect, test } from '@playwright/test'
import {
  interceptFetchUser,
  interceptLogout,
  MERCHANT_USER,
  seedAuthStorage,
  setupAuthenticatedMerchant,
} from './helpers/auth'

// ─── requiresAuth guard ───────────────────────────────────────────────────────

test.describe('requiresAuth guard', () => {
  // The guard fires synchronously for unauthenticated users (no async fetchUser),
  // so the redirect completes during page.goto(). Use toHaveURL() (polling) instead
  // of waitForURL() which requires a post-redirect "load" event that SPA navigation
  // never fires.

  test('unauthenticated user is redirected to /merchant/login', async ({ page }) => {
    await page.goto('/merchant/dashboard')
    await expect(page).toHaveURL(/\/merchant\/login/)
  })

  test('preserves the intended destination in ?redirect= query param', async ({ page }) => {
    await page.goto('/merchant/dashboard')
    await expect(page).toHaveURL(/\/merchant\/login.*redirect/)
    const url = new URL(page.url())
    expect(url.searchParams.get('redirect')).toBe('/merchant/dashboard')
  })

  test('unauthenticated user hitting /merchant/products is redirected to login', async ({
    page,
  }) => {
    await page.goto('/merchant/products')
    await expect(page).toHaveURL(/\/merchant\/login/)
  })
})

// ─── guestOnly guard ──────────────────────────────────────────────────────────

test.describe('guestOnly guard', () => {
  test('authenticated merchant visiting /merchant/login is redirected to dashboard', async ({
    page,
  }) => {
    await setupAuthenticatedMerchant(page)
    await page.goto('/merchant/login')

    await page.waitForURL('**/merchant/dashboard')
    await expect(page).toHaveURL('/merchant/dashboard')
  })

  test('authenticated merchant visiting /merchant/register is redirected to dashboard', async ({
    page,
  }) => {
    await setupAuthenticatedMerchant(page)
    await page.goto('/merchant/register')

    await page.waitForURL('**/merchant/dashboard')
    await expect(page).toHaveURL('/merchant/dashboard')
  })
})

// ─── Role guard ───────────────────────────────────────────────────────────────

test.describe('role guard', () => {
  test('authenticated merchant can access /merchant/dashboard', async ({ page }) => {
    await setupAuthenticatedMerchant(page)
    await page.goto('/merchant/dashboard')

    await expect(page).toHaveURL('/merchant/dashboard')
    await expect(page.getByText(`Good day, ${MERCHANT_USER.first_name}`)).toBeVisible()
  })

  test('authenticated merchant can access /merchant/products', async ({ page }) => {
    await setupAuthenticatedMerchant(page)
    await page.goto('/merchant/products')

    await expect(page).toHaveURL('/merchant/products')
    await expect(page.getByRole('heading', { name: 'Products' })).toBeVisible()
  })

  test('non-merchant authenticated user cannot access merchant dashboard', async ({ page }) => {
    const customerUser: AuthUser = { ...MERCHANT_USER, role: 'customer' }
    await seedAuthStorage(page, customerUser)
    await interceptFetchUser(page, { user: customerUser })

    await page.goto('/merchant/dashboard')

    // The role guard fires: customer.role !== 'merchant' → Vue Router redirects to
    // customer homeRoute (/homepage). Since that route is not yet implemented, Vue
    // Router detects a redirect loop and aborts the navigation without committing a
    // URL change. The DashboardPage component never mounts.
    await expect(page.getByText(`Good day, ${customerUser.first_name}`)).not.toBeVisible()
  })
})

// ─── Session expiry (401 interceptor) ────────────────────────────────────────

test.describe('session expiry', () => {
  test('a 401 response while authenticated clears the session and redirects to login', async ({
    page,
  }) => {
    // Establish a fully-authenticated session on the dashboard
    await setupAuthenticatedMerchant(page)
    // Mock logout to return 401 (simulates session expiry on the server)
    await interceptLogout(page, { status: 401 })

    await page.goto('/merchant/dashboard')
    await expect(page).toHaveURL('/merchant/dashboard')

    // The Sign out button triggers POST /logout → 401 → axios interceptor
    // → store.clearAuth() → router.push('/merchant/login')
    await page.getByRole('button', { name: 'Sign out' }).click()

    await page.waitForURL('**/merchant/login')
    await expect(page).toHaveURL(/\/merchant\/login/)
  })
})

// ─── EmailVerificationBanner in layout ───────────────────────────────────────

test.describe('email verification banner', () => {
  test('shows banner when merchant email is not verified', async ({ page }) => {
    await setupAuthenticatedMerchant(page, {
      ...MERCHANT_USER,
      is_email_verified: false,
    })

    await page.goto('/merchant/dashboard')

    await expect(page.getByText('Verify your email to unlock all features')).toBeVisible()
    await expect(page.getByRole('button', { name: 'Resend email' })).toBeVisible()
  })

  test('does not show banner when merchant email is verified', async ({ page }) => {
    await setupAuthenticatedMerchant(page, MERCHANT_USER) // is_email_verified: true

    await page.goto('/merchant/dashboard')

    await expect(page.getByText('Verify your email to unlock all features')).not.toBeVisible()
  })

  test('banner can be dismissed', async ({ page }) => {
    await setupAuthenticatedMerchant(page, {
      ...MERCHANT_USER,
      is_email_verified: false,
    })

    await page.goto('/merchant/dashboard')
    await expect(page.getByText('Verify your email to unlock all features')).toBeVisible()

    await page.getByRole('button', { name: 'Dismiss' }).click()

    await expect(page.getByText('Verify your email to unlock all features')).not.toBeVisible()
  })
})
