<script setup lang="ts">
import { toTypedSchema } from '@vee-validate/zod'
import { useForm } from 'vee-validate'
import { onMounted, ref } from 'vue'
import { toast } from 'vue3-toastify'
import { useRoute, useRouter } from 'vue-router'
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
const route = useRoute()
const router = useRouter()
const { redirectAfterLogin } = useAuthRedirect()
const isLoading = ref(false)

onMounted(() => {
  if (route.query.verified === '1') {
    toast.success('Email verified! You can now sign in to your merchant account.')
  }
})

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
    await auth.merchantLogin(values)
    toast.success('Welcome back!')
    await redirectAfterLogin()
  }
  catch (e: any) {
    if (e?.response?.status === 403) {
      // Email not verified — send them to the resend page so they don't hit a dead end
      router.push({ path: '/verify-email', query: { email: values.email, role: 'merchant' } })
      return
    }
    toast.error(e?.response?.data?.message ?? 'Login failed. Please try again.')
  }
  finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="theme-merchant min-h-screen bg-linear-to-br from-emerald-950 via-emerald-900 to-green-950 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Brand mark -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-emerald-700 border border-emerald-600 shadow-2xl mb-4">
          <span class="text-emerald-100 font-bold text-2xl">R</span>
        </div>
        <h1 class="text-2xl font-bold text-white">
          Merchant Portal
        </h1>
        <p class="text-emerald-300 mt-1 text-sm">
          Sign in to manage your business
        </p>
      </div>

      <Card class="bg-emerald-900/60 border-emerald-800 backdrop-blur-sm shadow-2xl rounded-2xl text-white">
        <CardHeader>
          <CardTitle class="text-white text-lg">
            Sign in
          </CardTitle>
        </CardHeader>

        <form @submit.prevent="onSubmit">
          <CardContent class="space-y-4">
            <FormField v-slot="{ componentField }" name="email">
              <FormItem>
                <FormLabel class="text-emerald-200">
                  Email address
                </FormLabel>
                <FormControl>
                  <Input
                    type="email"
                    placeholder="you@business.com"
                    autocomplete="email"
                    class="bg-emerald-950/50 border-emerald-700 text-white placeholder:text-emerald-600 focus-visible:border-emerald-500"
                    v-bind="componentField"
                  />
                </FormControl>
                <FormMessage class="text-red-400" />
              </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="password">
              <FormItem>
                <div class="flex items-center justify-between">
                  <FormLabel class="text-emerald-200">
                    Password
                  </FormLabel>
                  <RouterLink
                    to="/merchant/forgot-password"
                    class="text-xs text-emerald-400 hover:text-emerald-300 font-medium"
                  >
                    Forgot password?
                  </RouterLink>
                </div>
                <FormControl>
                  <Input
                    type="password"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    class="bg-emerald-950/50 border-emerald-700 text-white placeholder:text-emerald-600 focus-visible:border-emerald-500"
                    v-bind="componentField"
                  />
                </FormControl>
                <FormMessage class="text-red-400" />
              </FormItem>
            </FormField>
          </CardContent>

          <CardFooter class="flex flex-col gap-4 pt-2">
            <Button
              type="submit"
              class="w-full bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white font-semibold h-10 rounded-lg transition-colors border border-emerald-500"
              :disabled="isLoading"
            >
              {{ isLoading ? 'Signing in…' : 'Sign in to Merchant Portal' }}
            </Button>

            <p class="text-sm text-center text-emerald-400">
              New merchant?
              <RouterLink to="/merchant/register" class="text-emerald-300 hover:text-white font-semibold">
                Apply for an account
              </RouterLink>
            </p>
          </CardFooter>
        </form>
      </Card>
    </div>
  </div>
</template>
