<script setup lang="ts">
import { toTypedSchema } from '@vee-validate/zod'
import { useForm } from 'vee-validate'
import { ref } from 'vue'
import { toast } from 'vue3-toastify'
import { z } from 'zod'
import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { useAuthRedirect } from '@/composables/useAuthRedirect'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const { redirectAfterLogin } = useAuthRedirect()
const isLoading = ref(false)

const schema = toTypedSchema(
  z.object({
    email: z.string().min(1, 'Email is required').email('Enter a valid email address'),
    password: z.string().min(1, 'Password is required'),
  }),
)

const { handleSubmit } = useForm({ validationSchema: schema })

const onSubmit = handleSubmit(async (values) => {
  isLoading.value = true
  try {
    await auth.login(values)
    toast.success('Welcome back!')
    await redirectAfterLogin()
  }
  catch (e: any) {
    toast.error(e?.response?.data?.message ?? 'Login failed. Please try again.')
  }
  finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="theme-customer min-h-screen bg-gradient-to-br from-emerald-50 via-white to-teal-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Brand mark -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-emerald-500 shadow-lg mb-4">
          <span class="text-white font-bold text-2xl">R</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">
          Welcome back
        </h1>
        <p class="text-gray-500 mt-1 text-sm">
          Sign in to your account to continue
        </p>
      </div>

      <Card class="shadow-xl border-0 rounded-2xl">
        <CardHeader>
          <CardTitle class="text-lg">
            Sign in
          </CardTitle>
        </CardHeader>

        <form @submit.prevent="onSubmit">
          <CardContent class="space-y-4 -mt-4">
            <!-- Email -->
            <FormField v-slot="{ componentField }" name="email">
              <FormItem>
                <FormLabel>Email address</FormLabel>
                <FormControl>
                  <Input
                    type="email"
                    placeholder="you@example.com"
                    autocomplete="email"
                    v-bind="componentField"
                  />
                </FormControl>
                <FormMessage />
              </FormItem>
            </FormField>

            <!-- Password -->
            <FormField v-slot="{ componentField }" name="password">
              <FormItem>
                <div class="flex items-center justify-between">
                  <FormLabel>Password</FormLabel>
                  <RouterLink
                    to="/forgot-password"
                    class="text-xs text-emerald-600 hover:text-emerald-700 font-medium"
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
              class="w-full bg-emerald-500 hover:bg-emerald-600 active:bg-emerald-700 text-white font-semibold h-10 rounded-lg transition-colors"
              :disabled="isLoading"
            >
              {{ isLoading ? 'Signing in…' : 'Sign in' }}
            </Button>

            <p class="text-sm text-center text-gray-500">
              Don't have an account?
              <RouterLink to="/register" class="text-emerald-600 hover:text-emerald-700 font-semibold">
                Create one
              </RouterLink>
            </p>
          </CardFooter>
        </form>
      </Card>
    </div>
  </div>
</template>
