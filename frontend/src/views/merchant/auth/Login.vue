<script setup lang="ts">
import { Moon, Sun } from '@lucide/vue'
import { toTypedSchema } from '@vee-validate/zod'
import { useForm } from 'vee-validate'
import { ref } from 'vue'
import { toast } from 'vue3-toastify'
import { z } from 'zod'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { useAuthRedirect } from '@/composables/useAuthRedirect'
import { useColorMode } from '@/composables/useColorMode'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const { redirectAfterLogin } = useAuthRedirect()
const { isDark, toggle } = useColorMode()
const isLoading = ref(false)

const schema = toTypedSchema(
  z.object({
    email: z.string().min(1, 'Email is required').email('Enter a valid email address'),
    password: z.string().min(1, 'Password is required'),
  }),
)

const { handleSubmit } = useForm({
  validationSchema: schema,
  initialValues: { email: '', password: '' },
})

const onSubmit = handleSubmit(async (values) => {
  isLoading.value = true
  try {
    await auth.merchantLogin(values)
    await redirectAfterLogin()
    toast.success('Welcome back!')
  } catch (e: any) {
    toast.error(e?.response?.data?.message ?? 'Login failed. Please try again.')
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div
    class="relative min-h-screen flex items-center justify-center p-4 bg-linear-to-br from-auth-bg-from via-auth-bg-via to-auth-bg-to"
  >
    <!-- Dark mode toggle -->
    <button
      type="button"
      class="absolute top-4 right-4 p-2 rounded-lg text-subtle hover:text-foreground hover:bg-nav-hover transition-colors"
      :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
      @click="toggle"
    >
      <Sun v-if="isDark" class="w-5 h-5" aria-hidden="true" />
      <Moon v-else class="w-5 h-5" aria-hidden="true" />
    </button>

    <div class="w-full max-w-md">
      <!-- Brand mark -->
      <div class="text-center mb-8">
        <div
          class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-brand border border-brand/50 shadow-2xl mb-4"
        >
          <span class="text-brand-foreground font-bold text-2xl">R</span>
        </div>
        <h1 class="text-2xl font-bold text-foreground">Merchant Portal</h1>
        <p class="text-subtle mt-1 text-sm">Sign in to manage your business</p>
      </div>

      <Card class="bg-card border-border shadow-2xl rounded-2xl">
        <CardHeader>
          <CardTitle class="text-lg">Sign in</CardTitle>
        </CardHeader>

        <form @submit.prevent="onSubmit">
          <CardContent class="space-y-4">
            <FormField v-slot="{ componentField }" name="email">
              <FormItem>
                <FormLabel>Email address</FormLabel>
                <FormControl>
                  <Input
                    type="email"
                    placeholder="you@business.com"
                    autocomplete="email"
                    v-bind="componentField"
                  />
                </FormControl>
                <FormMessage />
              </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="password">
              <FormItem>
                <div class="flex items-center justify-between">
                  <FormLabel>Password</FormLabel>
                  <RouterLink
                    to="/merchant/forgot-password"
                    class="text-xs text-nav-text hover:text-foreground font-medium"
                  >
                    Forgot password?
                  </RouterLink>
                </div>
                <FormControl>
                  <Input
                    type="password"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    v-bind="componentField"
                  />
                </FormControl>
                <FormMessage />
              </FormItem>
            </FormField>
          </CardContent>

          <CardFooter class="flex flex-col gap-4 pt-2">
            <Button
              type="submit"
              class="w-full font-semibold h-10 rounded-lg bg-brand text-brand-foreground hover:bg-brand/90 border-0 shadow-none"
              :disabled="isLoading"
            >
              {{ isLoading ? 'Signing in…' : 'Sign in to Merchant Portal' }}
            </Button>

            <p class="text-sm text-center text-subtle">
              New merchant?
              <RouterLink
                to="/merchant/register"
                class="text-brand hover:text-foreground font-semibold"
              >
                Apply for an account
              </RouterLink>
            </p>
          </CardFooter>
        </form>
      </Card>
    </div>
  </div>
</template>
