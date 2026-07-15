import type { UserRole } from '@/types/auth'
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean
    role?: UserRole
    guestOnly?: boolean
  }
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', redirect: '/homepage' },

    // ── Customer ─────────────────────────────────────────────────
    // {
    //   path: '/homepage',
    //   component: () => import('@/views/customer/Homepage.vue'),
    // },
    // {
    //   path: '/login',
    //   component: () => import('@/views/customer/auth/Login.vue'),
    //   meta: { guestOnly: true },
    // },
    // {
    //   path: '/register',
    //   component: () => import('@/views/customer/auth/Register.vue'),
    //   meta: { guestOnly: true },
    // },
    // ── Shared: email verification ────────────────────────────────
    {
      // Handles the link the user clicks in their inbox (customer role)
      path: '/customer/email/verify/:id/:hash',
      component: () => import('@/views/shared/EmailVerifyCallback.vue'),
    },
    {
      // Handles the link the user clicks in their inbox (merchant role)
      path: '/merchant/email/verify/:id/:hash',
      component: () => import('@/views/shared/EmailVerifyCallback.vue'),
    },

    // ── Merchant ─────────────────────────────────────────────────
    {
      path: '/merchant/login',
      component: () => import('@/views/merchant/auth/Login.vue'),
      meta: { guestOnly: true },
    },
    {
      path: '/merchant/register',
      component: () => import('@/views/merchant/auth/Register.vue'),
      meta: { guestOnly: true },
    },
    {
      path: '/merchant',
      component: () => import('@/views/merchant/MerchantLayout.vue'),
      meta: { requiresAuth: true, role: 'merchant' },
      redirect: '/merchant/dashboard',
      children: [
        {
          path: 'dashboard',
          component: () => import('@/views/merchant/pages/dashboard/DashboardPage.vue'),
        },
        {
          path: 'products',
          component: () => import('@/views/merchant/pages/products/ProductsPage.vue'),
        },
      ],
    },

    // ── Admin ─────────────────────────────────────────────────────
    // {
    //   path: '/admin/login',
    //   component: () => import('@/views/admin/auth/Login.vue'),
    //   meta: { guestOnly: true },
    // },
    // {
    //   path: '/admin/dashboard',
    //   component: () => import('@/views/admin/Dashboard.vue'),
    //   meta: { requiresAuth: true, role: 'admin' },
    // },

    // ── Catch-all ─────────────────────────────────────────────────
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

router.afterEach((to) => {
  const html = document.documentElement
  html.classList.remove('theme-customer', 'theme-merchant', 'theme-admin')
  if (to.path.startsWith('/merchant') || to.query.role === 'merchant') {
    html.classList.add('theme-merchant')
  } else if (to.path.startsWith('/admin') || to.query.role === 'admin') {
    html.classList.add('theme-admin')
  } else {
    html.classList.add('theme-customer')
  }
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  // Lazy init: only hit the server when there's a persisted user to validate.
  // If user is null there's nothing to confirm — skip the round-trip and mark
  // as initialized so this block never runs again this session.
  if (!auth.initialized) {
    if (auth.user !== null) {
      await auth.fetchUser()
    } else {
      auth.initialized = true
    }
  }

  // Redirect authenticated users away from guest-only pages (e.g. /login)
  if (to.meta.guestOnly && auth.isAuthenticated) {
    return { path: auth.homeRoute }
  }

  // Redirect unauthenticated users to the correct portal login
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    if (to.path.startsWith('/admin'))
      return { path: '/admin/login', query: { redirect: to.fullPath } }
    if (to.path.startsWith('/merchant'))
      return { path: '/merchant/login', query: { redirect: to.fullPath } }
    return { path: '/login', query: { redirect: to.fullPath } }
  }

  // Redirect authenticated users who try to access a portal they don't belong to
  if (to.meta.role && auth.user?.role !== to.meta.role) {
    return { path: auth.homeRoute }
  }
})

export default router
