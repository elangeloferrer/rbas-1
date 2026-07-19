import { expect, test } from '@playwright/test'
import {
  interceptCsrf,
  interceptFetchUser,
  interceptMerchantRegister,
  MERCHANT_USER,
  setupAuthenticatedMerchant,
} from './helpers/auth'

test.describe('Merchant Registration', () => {
  test('renders the registration form with all required fields', async ({ page }) => {
    await page.goto('/merchant/register')

    await expect(page.getByRole('heading', { name: 'Join as a Merchant' })).toBeVisible()
    await expect(page.getByLabel('Your name')).toBeVisible()
    await expect(page.getByLabel('Business email')).toBeVisible()
    await expect(page.getByLabel('Password', { exact: true })).toBeVisible()
    await expect(page.getByLabel('Confirm password')).toBeVisible()
    await expect(page.getByRole('button', { name: 'Create merchant account' })).toBeVisible()
    await expect(page.getByRole('link', { name: 'Sign in' })).toBeVisible()
  })

  test('shows validation error when name is too short', async ({ page }) => {
    await page.goto('/merchant/register')

    await page.getByLabel('Your name').fill('J')
    await page.getByRole('button', { name: 'Create merchant account' }).click()

    await expect(page.getByText('Name must be at least 2 characters')).toBeVisible()
  })

  test('shows password strength validation errors', async ({ page }) => {
    await page.goto('/merchant/register')

    await page.getByLabel('Your name').fill('Jane')
    await page.getByLabel('Business email').fill('jane@business.com')
    await page.getByLabel('Password', { exact: true }).fill('short')
    await page.getByRole('button', { name: 'Create merchant account' }).click()

    await expect(page.getByText('Password must be at least 8 characters')).toBeVisible()
  })

  test('shows validation error on password mismatch', async ({ page }) => {
    await page.goto('/merchant/register')

    await page.getByLabel('Your name').fill('Jane')
    await page.getByLabel('Business email').fill('jane@business.com')
    await page.getByLabel('Password', { exact: true }).fill('Password1!')
    await page.getByLabel('Confirm password').fill('Different1!')
    await page.getByRole('button', { name: 'Create merchant account' }).click()

    await expect(page.getByText("Passwords don't match")).toBeVisible()
  })

  test('shows loading state while registration request is in flight', async ({ page }) => {
    await interceptCsrf(page)

    let unblockRegister!: () => void
    const registerGate = new Promise<void>((resolve) => {
      unblockRegister = resolve
    })
    await page.route('**/api/merchant/register', async (route) => {
      await registerGate
      await route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'OK' }),
      })
    })
    await interceptFetchUser(page, { user: MERCHANT_USER })

    await page.goto('/merchant/register')
    await page.getByLabel('Your name').fill('Jane')
    await page.getByLabel('Business email').fill('jane@business.com')
    await page.getByLabel('Password', { exact: true }).fill('Password1!')
    await page.getByLabel('Confirm password').fill('Password1!')
    await page.getByRole('button', { name: 'Create merchant account' }).click()

    const loadingBtn = page.getByRole('button', { name: 'Creating account…' })
    await expect(loadingBtn).toBeVisible()
    await expect(loadingBtn).toBeDisabled()

    unblockRegister()
    await page.waitForURL('**/merchant/dashboard')
  })

  test('redirects to dashboard and shows welcome toast on success', async ({ page }) => {
    await interceptCsrf(page)
    await interceptMerchantRegister(page, { success: true })
    await interceptFetchUser(page, { user: MERCHANT_USER })

    await page.goto('/merchant/register')
    await page.getByLabel('Your name').fill('Jane')
    await page.getByLabel('Business email').fill('jane@business.com')
    await page.getByLabel('Password', { exact: true }).fill('Password1!')
    await page.getByLabel('Confirm password').fill('Password1!')
    await page.getByRole('button', { name: 'Create merchant account' }).click()

    await page.waitForURL('**/merchant/dashboard')
    await expect(
      page.getByText('Welcome! A verification email has been sent to your inbox.'),
    ).toBeVisible()
  })

  test('shows server error toast for duplicate email', async ({ page }) => {
    await interceptCsrf(page)
    await interceptMerchantRegister(page, {
      success: false,
      status: 422,
      errors: { email: ['The email has already been taken.'] },
    })

    await page.goto('/merchant/register')
    await page.getByLabel('Your name').fill('Jane')
    await page.getByLabel('Business email').fill('jane@business.com')
    await page.getByLabel('Password', { exact: true }).fill('Password1!')
    await page.getByLabel('Confirm password').fill('Password1!')
    await page.getByRole('button', { name: 'Create merchant account' }).click()

    await expect(page.getByText('The email has already been taken.')).toBeVisible()
    await expect(page).toHaveURL('/merchant/register')
  })

  test('"Sign in" link navigates to the login page', async ({ page }) => {
    await page.goto('/merchant/register')
    await page.getByRole('link', { name: 'Sign in' }).click()
    await expect(page).toHaveURL('/merchant/login')
  })

  test('authenticated merchant is redirected away from register (guestOnly guard)', async ({
    page,
  }) => {
    await setupAuthenticatedMerchant(page)
    await page.goto('/merchant/register')

    await page.waitForURL('**/merchant/dashboard')
    await expect(page).toHaveURL('/merchant/dashboard')
  })
})
