<script setup lang="ts">
import { Moon, Sun } from '@lucide/vue'
import { toTypedSchema } from '@vee-validate/zod'
import { useForm } from 'vee-validate'
import { computed, ref, watch } from 'vue'
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
import { FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { useAuthRedirect } from '@/composables/useAuthRedirect'
import { useColorMode } from '@/composables/useColorMode'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const { redirectAfterLogin } = useAuthRedirect()
const { isDark, toggle } = useColorMode()
const isLoading = ref(false)

const passwordValue = ref('')

const validationSchema = computed(() =>
  toTypedSchema(
    z.object({
      first_name: z
        .string()
        .min(2, 'Name must be at least 2 characters')
        .max(50, 'Name is too long'),
      email: z.string().min(1, 'Email is required').email('Enter a valid email address'),
      password: z
        .string()
        .min(8, 'Password must be at least 8 characters')
        .regex(/[A-Z]/, 'Must contain at least one uppercase letter')
        .regex(/\d/, 'Must contain at least one number')
        .regex(/[^a-z0-9]/i, 'Must contain at least one special character'),
      password_confirmation: z
        .string()
        .min(1, 'Please confirm your password')
        .refine((val) => val === passwordValue.value, "Passwords don't match"),
    }),
  ),
)

const { handleSubmit, values } = useForm({ validationSchema })

watch(
  () => values.password,
  (val) => {
    passwordValue.value = val ?? ''
  },
)

const onSubmit = handleSubmit(async (values) => {
  isLoading.value = true
  try {
    await auth.merchantRegister(values)
    await redirectAfterLogin()
    toast.success('Welcome! A verification email has been sent to your inbox.')
  } catch (e: any) {
    const errors = e?.response?.data?.errors
    if (errors) {
      const first = Object.values(errors)[0] as string[]
      toast.error(first[0])
    } else {
      toast.error(e?.response?.data?.message ?? 'Registration failed. Please try again.')
    }
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div
    class="relative min-h-screen flex items-center justify-center p-4 py-12 bg-linear-to-br from-auth-bg-from via-auth-bg-via to-auth-bg-to"
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
      <div class="text-center mb-8">
        <div
          class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-brand border border-brand/50 shadow-2xl mb-4"
        >
          <span class="text-brand-foreground font-bold text-2xl">R</span>
        </div>
        <h1 class="text-2xl font-bold text-foreground">Join as a Merchant</h1>
        <p class="text-subtle mt-1 text-sm">Start selling to customers on RBAS</p>
      </div>

      <Card class="bg-card border-border shadow-2xl rounded-2xl">
        <CardHeader>
          <CardTitle class="text-lg">Create merchant account</CardTitle>
          <CardDescription>Fill in your details to get started</CardDescription>
        </CardHeader>

        <form @submit.prevent="onSubmit">
          <CardContent class="space-y-4">
            <FormField v-slot="{ componentField }" name="first_name">
              <FormItem>
                <FormLabel>Your name</FormLabel>
                <FormControl>
                  <Input
                    type="text"
                    placeholder="Jane"
                    autocomplete="given-name"
                    v-bind="componentField"
                  />
                </FormControl>
                <FormMessage />
              </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="email">
              <FormItem>
                <FormLabel>Business email</FormLabel>
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
                <FormLabel>Password</FormLabel>
                <FormControl>
                  <Input
                    type="password"
                    placeholder="Min. 8 characters"
                    autocomplete="new-password"
                    v-bind="componentField"
                  />
                </FormControl>
                <FormMessage />
              </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="password_confirmation">
              <FormItem>
                <FormLabel>Confirm password</FormLabel>
                <FormControl>
                  <Input
                    type="password"
                    placeholder="Re-enter your password"
                    autocomplete="new-password"
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
              {{ isLoading ? 'Creating account…' : 'Create merchant account' }}
            </Button>

            <p class="text-xs text-center text-subtle leading-relaxed">
              By registering you agree to our
              <a href="#" class="text-brand hover:underline">Terms of Service</a>
              and
              <a href="#" class="text-brand hover:underline">Privacy Policy</a>.
            </p>

            <p class="text-sm text-center text-subtle">
              Already have an account?
              <RouterLink
                to="/merchant/login"
                class="text-brand hover:text-foreground font-semibold"
              >
                Sign in
              </RouterLink>
            </p>
          </CardFooter>
        </form>
      </Card>
    </div>
  </div>
</template>
