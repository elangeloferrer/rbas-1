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
    {
      path: '/homepage',
      component: () => import('@/views/customer/Homepage.vue'),
    },
    {
      path: '/login',
      component: () => import('@/views/customer/auth/Login.vue'),
      meta: { guestOnly: true },
    },
    {
      path: '/register',
      component: () => import('@/views/customer/auth/Register.vue'),
      meta: { guestOnly: true },
    },
    // {
    //   path: '/dashboard',
    //   component: () => import('@/views/customer/Dashboard.vue'),
    //   meta: { requiresAuth: true, role: 'customer' },
    // },

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
      path: '/merchant/dashboard',
      component: () => import('@/views/merchant/Dashboard.vue'),
      meta: { requiresAuth: true, role: 'merchant' },
    },

    // ── Admin ─────────────────────────────────────────────────────
    {
      path: '/admin/login',
      component: () => import('@/views/admin/auth/Login.vue'),
      meta: { guestOnly: true },
    },
    {
      path: '/admin/dashboard',
      component: () => import('@/views/admin/Dashboard.vue'),
      meta: { requiresAuth: true, role: 'admin' },
    },

    // ── Catch-all ─────────────────────────────────────────────────
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  // Fetch the authenticated user once per session (lazy init)
  if (!auth.initialized) {
    await auth.fetchUser()
  }

  // Redirect authenticated users away from guest-only pages (e.g. /login)
  if (to.meta.guestOnly && auth.isAuthenticated) {
    return { path: auth.homeRoute }
  }

  // Redirect unauthenticated users to the correct portal login
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    if (to.path.startsWith('/admin')) return { path: '/admin/login', query: { redirect: to.fullPath } }
    if (to.path.startsWith('/merchant')) return { path: '/merchant/login', query: { redirect: to.fullPath } }
    return { path: '/login', query: { redirect: to.fullPath } }
  }

  // Redirect authenticated users who try to access a portal they don't belong to
  if (to.meta.role && auth.user?.role !== to.meta.role) {
    return { path: auth.homeRoute }
  }
})

export default router
