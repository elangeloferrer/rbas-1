import { expect, test } from '@playwright/test'
import {
  interceptEmailResend,
  interceptEmailVerify,
  setupAuthenticatedMerchant,
} from './helpers/auth'

// Fake verification URL params — the backend validates these; we mock the POST.
const VERIFY_URL = '/merchant/email/verify/1/abc123?expires=9999999999&signature=testsig'

test.describe('Email Verification Callback', () => {
  // ─── Loading state ────────────────────────────────────────────────────────

  test('shows loading state while the verification request is in flight', async ({ page }) => {
    let unblockVerify!: () => void
    const verifyGate = new Promise<void>((resolve) => {
      unblockVerify = resolve
    })
    await page.route('**/api/merchant/email/verify', async (route) => {
      await verifyGate
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'Email verified.' }),
      })
    })

    await page.goto(VERIFY_URL)
    await expect(page.getByText('Verifying your email…')).toBeVisible()

    unblockVerify()
  })

  // ─── Success state (unauthenticated) ──────────────────────────────────────

  test('shows success state after successful verification (unauthenticated)', async ({ page }) => {
    await interceptEmailVerify(page, { success: true })

    await page.goto(VERIFY_URL)

    await expect(page.getByRole('heading', { name: 'Email verified!' })).toBeVisible()
    await expect(page.getByText('Your account is now active.')).toBeVisible()
    await expect(page.getByRole('button', { name: 'Sign in now' })).toBeVisible()
  })

  test('countdown text is shown on success (unauthenticated)', async ({ page }) => {
    await interceptEmailVerify(page, { success: true })

    await page.goto(VERIFY_URL)

    // The countdown starts at 5 — assert the copy before it reaches 0
    await expect(page.getByText('Redirecting you to sign in in')).toBeVisible()
  })

  test('"Sign in now" button navigates to login with ?verified=1 (unauthenticated)', async ({
    page,
  }) => {
    await interceptEmailVerify(page, { success: true })

    await page.goto(VERIFY_URL)
    await expect(page.getByRole('button', { name: 'Sign in now' })).toBeVisible()
    await page.getByRole('button', { name: 'Sign in now' }).click()

    await page.waitForURL('**/merchant/login**')
    const url = new URL(page.url())
    expect(url.searchParams.get('verified')).toBe('1')
  })

  // ─── Success state (authenticated) ────────────────────────────────────────

  test('shows "Go to Dashboard" button when authenticated user verifies', async ({ page }) => {
    // User is authenticated — setupAuthenticatedMerchant seeds storage + mocks /api/user
    // The component calls auth.fetchUser() post-verify to refresh is_email_verified.
    await setupAuthenticatedMerchant(page)
    await interceptEmailVerify(page, { success: true })

    await page.goto(VERIFY_URL)

    await expect(page.getByRole('heading', { name: 'Email verified!' })).toBeVisible()
    await expect(page.getByRole('button', { name: 'Go to Dashboard' })).toBeVisible()
    await expect(page.getByText('Taking you to your dashboard in')).toBeVisible()
  })

  test('"Go to Dashboard" button navigates to merchant dashboard', async ({ page }) => {
    await setupAuthenticatedMerchant(page)
    await interceptEmailVerify(page, { success: true })

    await page.goto(VERIFY_URL)
    await expect(page.getByRole('button', { name: 'Go to Dashboard' })).toBeVisible()
    await page.getByRole('button', { name: 'Go to Dashboard' }).click()

    await page.waitForURL('**/merchant/dashboard')
    await expect(page).toHaveURL('/merchant/dashboard')
  })

  // ─── Error state (unauthenticated) ────────────────────────────────────────

  test('shows error state when verification link is invalid (unauthenticated)', async ({
    page,
  }) => {
    await interceptEmailVerify(page, {
      success: false,
      status: 422,
      message: 'This verification link has expired.',
    })

    await page.goto(VERIFY_URL)

    await expect(page.getByRole('heading', { name: 'Verification failed' })).toBeVisible()
    await expect(page.getByText('This verification link has expired.')).toBeVisible()
  })

  test('unauthenticated error state shows "Back to sign in" button', async ({ page }) => {
    await interceptEmailVerify(page, { success: false, status: 422 })

    await page.goto(VERIFY_URL)

    await expect(page.getByRole('button', { name: 'Back to sign in' })).toBeVisible()
    // No resend button — unauthenticated users must log in first
    await expect(page.getByRole('button', { name: 'Resend verification email' })).not.toBeVisible()
  })

  test('"Back to sign in" navigates to merchant login (unauthenticated error)', async ({
    page,
  }) => {
    await interceptEmailVerify(page, { success: false, status: 422 })

    await page.goto(VERIFY_URL)
    await page.getByRole('button', { name: 'Back to sign in' }).click()

    await expect(page).toHaveURL('/merchant/login')
  })

  // ─── Error state (authenticated) ──────────────────────────────────────────

  test('authenticated error state shows resend button and dashboard link', async ({ page }) => {
    await setupAuthenticatedMerchant(page)
    await interceptEmailVerify(page, { success: false, status: 422 })

    await page.goto(VERIFY_URL)

    await expect(page.getByRole('button', { name: 'Resend verification email' })).toBeVisible()
    await expect(page.getByRole('button', { name: 'Go to dashboard' })).toBeVisible()
  })

  // ─── Resent state ─────────────────────────────────────────────────────────

  test('resend transitions to "New link sent!" state', async ({ page }) => {
    await setupAuthenticatedMerchant(page)
    await interceptEmailVerify(page, { success: false, status: 422 })
    await interceptEmailResend(page, { success: true })

    await page.goto(VERIFY_URL)
    await expect(page.getByRole('button', { name: 'Resend verification email' })).toBeVisible()
    await page.getByRole('button', { name: 'Resend verification email' }).click()

    await expect(page.getByRole('heading', { name: 'New link sent!' })).toBeVisible()
    await expect(
      page.getByText("We've sent a fresh verification link to your email."),
    ).toBeVisible()
  })

  test('resend rate-limited response shows retry banner', async ({ page }) => {
    await setupAuthenticatedMerchant(page)
    await interceptEmailVerify(page, { success: false, status: 422 })
    await interceptEmailResend(page, { success: false, status: 429, retryAfter: 60 })

    await page.goto(VERIFY_URL)
    await page.getByRole('button', { name: 'Resend verification email' }).click()

    // RateLimitBanner renders: "<title> — please wait <countdown> before trying again."
    await expect(page.getByText(/Too many requests\. — please wait/)).toBeVisible()
  })

  // ─── AbortController timeout ──────────────────────────────────────────────

  test('shows timeout error when verify request is aborted', async ({ page }) => {
    // Never fulfill the request — the component's 15 s AbortController will fire.
    // We speed it up by overriding the timeout via JS before page load.
    await page.addInitScript(() => {
      // Monkey-patch setTimeout to fire the abort controller immediately
      const originalSetTimeout = window.setTimeout
      ;(window as any).setTimeout = (fn: () => void, delay: number) => {
        // Only collapse the 15000ms AbortController timeout; leave shorter timers alone
        return originalSetTimeout(fn, delay === 15000 ? 0 : delay)
      }
    })
    await page.route('**/api/merchant/email/verify', async (route) => {
      // Hold indefinitely — the AbortController should cancel this
      await new Promise(() => {})
      route.fulfill({ status: 200 })
    })

    await page.goto(VERIFY_URL)

    await expect(page.getByRole('heading', { name: 'Verification failed' })).toBeVisible()
    await expect(
      page.getByText('Verification timed out. Please check your connection and try again.'),
    ).toBeVisible()
  })
})
