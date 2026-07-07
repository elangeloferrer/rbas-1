import type { AuthUser, LoginPayload, RegisterPayload } from '@/types/auth'
import { defineStore } from 'pinia'
import api, { getCsrfCookie } from '@/lib/axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as AuthUser | null,
    initialized: false,
  }),

  getters: {
    isAuthenticated: (state): boolean => state.user !== null,

    homeRoute: (state): string => {
      if (state.user?.role === 'admin')
        return '/admin/dashboard'
      if (state.user?.role === 'merchant')
        return '/merchant/dashboard'
      return '/dashboard'
    },
  },

  actions: {
    async fetchUser(): Promise<void> {
      try {
        const { data } = await api.get<{ data: AuthUser }>('/user')
        this.user = data.data
      }
      catch {
        this.user = null
      }
      finally {
        this.initialized = true
      }
    },

    async login(payload: LoginPayload): Promise<void> {
      await getCsrfCookie()
      await api.post('/login', payload)
      await this.fetchUser()
    },

    async register(payload: RegisterPayload): Promise<void> {
      await getCsrfCookie()
      await api.post('/register', payload)
      await this.fetchUser()
    },

    async merchantLogin(payload: LoginPayload): Promise<void> {
      await getCsrfCookie()
      await api.post('/merchant/login', payload)
      await this.fetchUser()
    },

    async merchantRegister(payload: RegisterPayload): Promise<void> {
      await getCsrfCookie()
      await api.post('/merchant/register', payload)
      await this.fetchUser()
    },

    async adminLogin(payload: LoginPayload): Promise<void> {
      await getCsrfCookie()
      await api.post('/admin/login', payload)
      await this.fetchUser()
    },

    async logout(): Promise<void> {
      await api.post('/logout')
      this.clearAuth()
    },

    clearAuth(): void {
      this.user = null
      this.initialized = true
    },
  },

  persist: {
    pick: ['user'],
  },
})
