import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

export function useAuthRedirect() {
  const router = useRouter()
  const route = useRoute()
  const auth = useAuthStore()

  async function redirectAfterLogin(): Promise<void> {
    const redirect = route.query.redirect as string | undefined
    await router.push(redirect ?? auth.homeRoute)
  }

  return { redirectAfterLogin }
}
