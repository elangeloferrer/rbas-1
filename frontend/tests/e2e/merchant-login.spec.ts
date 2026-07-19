import { expect, test } from '@playwright/test'
import {
  interceptCsrf,
  interceptFetchUser,
  interceptMerchantLogin,
  MERCHANT_USER,
  setupAuthenticatedMerchant,
} from './helpers/auth'

test.describe('Merchant Login', () => {
  test('renders the login form with all required elements', async ({ page }) => {
    await page.goto('/merchant/login')

    await expect(page.getByRole('heading', { name: 'Merchant Portal' })).toBeVisible()
    await expect(page.getByLabel('Email address')).toBeVisible()
    await expect(page.getByLabel('Password')).toBeVisible()
    await expect(page.getByRole('button', { name: 'Sign in to Merchant Portal' })).toBeVisible()
    await expect(page.getByRole('link', { name: 'Forgot password?' })).toBeVisible()
    await expect(page.getByRole('link', { name: 'Apply for an account' })).toBeVisible()
  })

  test('shows field-level validation errors on empty submission', async ({ page }) => {
    await page.goto('/merchant/login')

    await page.getByRole('button', { name: 'Sign in to Merchant Portal' }).click()

    await expect(page.getByText('Email is required')).toBeVisible()
    await expect(page.getByText('Password is required')).toBeVisible()
  })

  test('shows validation error for malformed email', async ({ page }) => {
    await page.goto('/merchant/login')

    await page.getByLabel('Email address').fill('not-an-email')
    await page.getByLabel('Password').fill('anypassword')
    await page.getByRole('button', { name: 'Sign in to Merchant Portal' }).click()

    await expect(page.getByText('Enter a valid email address')).toBeVisible()
  })

  test('shows loading state while the login request is in flight', async ({ page }) => {
    await interceptCsrf(page)

    // Block the login request until we explicitly release it
    let unblockLogin!: () => void
    const loginGate = new Promise<void>((resolve) => {
      unblockLogin = resolve
    })
    await page.route('**/api/merchant/login', async (route) => {
      await loginGate
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'OK' }),
      })
    })
    await interceptFetchUser(page, { user: MERCHANT_USER })

    await page.goto('/merchant/login')
    await page.getByLabel('Email address').fill('jane@business.com')
    await page.getByLabel('Password').fill('Password1!')
    await page.getByRole('button', { name: 'Sign in to Merchant Portal' }).click()

    const loadingBtn = page.getByRole('button', { name: 'Signing in…' })
    await expect(loadingBtn).toBeVisible()
    await expect(loadingBtn).toBeDisabled()

    // Release the gate so the test cleans up properly
    unblockLogin()
    await page.waitForURL('**/merchant/dashboard')
  })

  test('shows error toast on invalid credentials', async ({ page }) => {
    await interceptCsrf(page)
    await interceptMerchantLogin(page, {
      success: false,
      status: 422,
      message: 'These credentials do not match our records.',
    })

    await page.goto('/merchant/login')
    await page.getByLabel('Email address').fill('wrong@business.com')
    await page.getByLabel('Password').fill('WrongPass1!')
    await page.getByRole('button', { name: 'Sign in to Merchant Portal' }).click()

    await expect(page.getByText('These credentials do not match our records.')).toBeVisible()
    await expect(page).toHaveURL('/merchant/login')
  })

  test('redirects to the merchant dashboard on successful login', async ({ page }) => {
    await interceptCsrf(page)
    await interceptMerchantLogin(page, { success: true })
    await interceptFetchUser(page, { user: MERCHANT_USER })

    await page.goto('/merchant/login')
    await page.getByLabel('Email address').fill('jane@business.com')
    await page.getByLabel('Password').fill('Password1!')
    await page.getByRole('button', { name: 'Sign in to Merchant Portal' }).click()

    await page.waitForURL('**/merchant/dashboard')
    await expect(page.getByText(`Good day, ${MERCHANT_USER.first_name}`)).toBeVisible()
  })

  test('honors the ?redirect= query param after login', async ({ page }) => {
    await interceptCsrf(page)
    await interceptMerchantLogin(page, { success: true })
    await interceptFetchUser(page, { user: MERCHANT_USER })

    await page.goto('/merchant/login?redirect=/merchant/products')
    await page.getByLabel('Email address').fill('jane@business.com')
    await page.getByLabel('Password').fill('Password1!')
    await page.getByRole('button', { name: 'Sign in to Merchant Portal' }).click()

    await page.waitForURL('**/merchant/products')
    await expect(page).toHaveURL('/merchant/products')
  })

  test('"Apply for an account" link navigates to the register page', async ({ page }) => {
    await page.goto('/merchant/login')
    await page.getByRole('link', { name: 'Apply for an account' }).click()
    await expect(page).toHaveURL('/merchant/register')
  })

  test('authenticated merchant is redirected away from login (guestOnly guard)', async ({
    page,
  }) => {
    await setupAuthenticatedMerchant(page)
    await page.goto('/merchant/login')

    await page.waitForURL('**/merchant/dashboard')
    await expect(page).toHaveURL('/merchant/dashboard')
  })
})
