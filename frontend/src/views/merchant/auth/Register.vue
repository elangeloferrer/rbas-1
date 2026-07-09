<script setup lang="ts">
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

// Tracks current password value so the schema can close over it reactively
const passwordValue = ref('')

// computed schema: rebuilds whenever passwordValue changes so the
// password_confirmation .refine() always compares against the latest password
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
        .refine(val => val === passwordValue.value, 'Passwords don\'t match'),
    }),
  ),
)

const { handleSubmit, values } = useForm({ validationSchema })

// Keep passwordValue in sync so the schema refine always has the latest value
watch(() => values.password, (val) => {
  passwordValue.value = val ?? ''
})

const onSubmit = handleSubmit(async (values) => {
  isLoading.value = true
  try {
    await auth.merchantRegister(values)
    toast.success('Welcome! A verification email has been sent to your inbox.')
    await redirectAfterLogin()
  }
  catch (e: any) {
    const errors = e?.response?.data?.errors
    if (errors) {
      const first = Object.values(errors)[0] as string[]
      toast.error(first[0])
    }
    else {
      toast.error(e?.response?.data?.message ?? 'Registration failed. Please try again.')
    }
  }
  finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="theme-merchant min-h-screen bg-gradient-to-br from-emerald-950 via-emerald-900 to-green-950 flex items-center justify-center p-4 py-12">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-emerald-700 border border-emerald-600 shadow-2xl mb-4">
          <span class="text-emerald-100 font-bold text-2xl">R</span>
        </div>
        <h1 class="text-2xl font-bold text-white">
          Join as a Merchant
        </h1>
        <p class="text-emerald-300 mt-1 text-sm">
          Start selling to customers on RBAS
        </p>
      </div>

      <Card class="bg-emerald-900/60 border-emerald-800 backdrop-blur-sm shadow-2xl rounded-2xl text-white">
        <CardHeader>
          <CardTitle class="text-white text-lg">
            Create merchant account
          </CardTitle>
          <CardDescription class="text-emerald-300">
            Fill in your details to get started
          </CardDescription>
        </CardHeader>

        <form @submit.prevent="onSubmit">
          <CardContent class="space-y-4">
            <FormField v-slot="{ componentField }" name="first_name">
              <FormItem>
                <FormLabel class="text-emerald-200">
                  Your name
                </FormLabel>
                <FormControl>
                  <Input
                    type="text"
                    placeholder="Jane"
                    autocomplete="given-name"
                    class="bg-emerald-950/50 border-emerald-700 text-white placeholder:text-emerald-600 focus-visible:border-emerald-500"
                    v-bind="componentField"
                  />
                </FormControl>
                <FormMessage class="text-red-400" />
              </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="email">
              <FormItem>
                <FormLabel class="text-emerald-200">
                  Business email
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
                <FormLabel class="text-emerald-200">
                  Password
                </FormLabel>
                <FormControl>
                  <Input
                    type="password"
                    placeholder="Min. 8 characters"
                    autocomplete="new-password"
                    class="bg-emerald-950/50 border-emerald-700 text-white placeholder:text-emerald-600 focus-visible:border-emerald-500"
                    v-bind="componentField"
                  />
                </FormControl>
                <FormMessage class="text-red-400" />
              </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="password_confirmation">
              <FormItem>
                <FormLabel class="text-emerald-200">
                  Confirm password
                </FormLabel>
                <FormControl>
                  <Input
                    type="password"
                    placeholder="Re-enter your password"
                    autocomplete="new-password"
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
              class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-semibold h-10 rounded-lg transition-colors border border-emerald-500"
              :disabled="isLoading"
            >
              {{ isLoading ? 'Creating account…' : 'Create merchant account' }}
            </Button>

            <p class="text-xs text-center text-emerald-500 leading-relaxed">
              By registering you agree to our
              <a href="#" class="text-emerald-400 hover:underline">Terms of Service</a>
              and
              <a href="#" class="text-emerald-400 hover:underline">Privacy Policy</a>.
            </p>

            <p class="text-sm text-center text-emerald-400">
              Already have an account?
              <RouterLink to="/merchant/login" class="text-emerald-300 hover:text-white font-semibold">
                Sign in
              </RouterLink>
            </p>
          </CardFooter>
        </form>
      </Card>
    </div>
  </div>
</template>
