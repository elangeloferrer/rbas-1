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

    // --- Customer ---

    // --- Merchant ---

    // --- Admin ---
    { path: '/admin/login', component: () => import('@/views/admin/Login.vue'), meta: { guestOnly: true } },
    { path: '/admin/dashboard', component: () => import('@/views/admin/Dashboard.vue'), meta: { requiresAuth: true, role: 'admin' } },

    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (!auth.initialized) {
    await auth.fetchUser()
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return { path: auth.homeRoute }
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    if (to.path.startsWith('/admin')) return { path: '/admin/login', query: { redirect: to.fullPath } }
    if (to.path.startsWith('/merchant')) return { path: '/merchant/login', query: { redirect: to.fullPath } }
    return { path: '/login', query: { redirect: to.fullPath } }
  }

  if (to.meta.role && auth.user?.role !== to.meta.role) {
    return { path: auth.homeRoute }
  }
})

export default router