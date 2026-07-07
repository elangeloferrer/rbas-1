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
  CardDescription,
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
    await auth.adminLogin(values)

    if (auth.user?.role !== 'admin') {
      await auth.logout()
      toast.error('Access denied. Admin accounts only.')
      return
    }

    toast.success('Welcome, Administrator.')
    await redirectAfterLogin()
  }
  catch (e: any) {
    toast.error(e?.response?.data?.message ?? 'Login failed.')
  }
  finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="theme-admin min-h-screen bg-slate-950 flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
      <!-- Brand mark -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-slate-700 border border-slate-600 mb-4">
          <span class="text-slate-200 font-bold text-lg">R</span>
        </div>
        <h1 class="text-xl font-bold text-white">
          Admin Portal
        </h1>
        <p class="text-slate-400 mt-1 text-sm">
          Restricted access — authorized personnel only
        </p>
      </div>

      <Card class="bg-slate-800/80 border-slate-700 backdrop-blur-sm rounded-xl shadow-2xl text-white">
        <CardHeader>
          <CardTitle class="text-white text-base">
            Administrator sign in
          </CardTitle>
          <CardDescription class="text-slate-400 text-sm">
            Enter your admin credentials
          </CardDescription>
        </CardHeader>

        <form @submit.prevent="onSubmit">
          <CardContent class="space-y-4">
            <FormField v-slot="{ componentField }" name="email">
              <FormItem>
                <FormLabel class="text-slate-300 text-sm">
                  Email address
                </FormLabel>
                <FormControl>
                  <Input
                    type="email"
                    placeholder="admin@example.com"
                    autocomplete="email"
                    class="bg-slate-900/70 border-slate-600 text-white placeholder:text-slate-600 focus-visible:border-slate-400"
                    v-bind="componentField"
                  />
                </FormControl>
                <FormMessage class="text-red-400 text-xs" />
              </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="password">
              <FormItem>
                <FormLabel class="text-slate-300 text-sm">
                  Password
                </FormLabel>
                <FormControl>
                  <Input
                    type="password"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    class="bg-slate-900/70 border-slate-600 text-white placeholder:text-slate-600 focus-visible:border-slate-400"
                    v-bind="componentField"
                  />
                </FormControl>
                <FormMessage class="text-red-400 text-xs" />
              </FormItem>
            </FormField>
          </CardContent>

          <CardFooter class="flex flex-col gap-3 pt-2">
            <Button
              type="submit"
              class="w-full bg-slate-600 hover:bg-slate-500 active:bg-slate-700 text-white font-semibold h-10 rounded-lg transition-colors border border-slate-500"
              :disabled="isLoading"
            >
              {{ isLoading ? 'Signing in…' : 'Sign in' }}
            </Button>
          </CardFooter>
        </form>
      </Card>
    </div>
  </div>
</template>
