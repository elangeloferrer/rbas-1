import axios from 'axios'

const api = axios.create({
  baseURL: '/api',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

api.interceptors.response.use(
  response => response,
  async (error) => {
    if (error.response?.status === 401) {
      const { useAuthStore } = await import('@/stores/auth')
      const store = useAuthStore()

      if (store.initialized && store.isAuthenticated) {
        store.clearAuth()
        const { default: router } = await import('@/router')
        const path = router.currentRoute.value.path
        if (path.startsWith('/admin')) {
          await router.push('/admin/login')
        }
        else if (path.startsWith('/merchant')) {
          await router.push('/merchant/login')
        }
        else {
          await router.push('/login')
        }
      }
    }
    return Promise.reject(error)
  },
)

export async function getCsrfCookie(): Promise<void> {
  await axios.get('/sanctum/csrf-cookie', {
    baseURL: '/',
    withCredentials: true,
  })
}

export default api