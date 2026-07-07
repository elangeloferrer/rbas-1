export type UserRole = 'customer' | 'merchant' | 'admin'

export interface AuthUser {
  first_name: string
  email: string
  role: UserRole
}

export interface LoginPayload {
  email: string
  password: string
}

export interface RegisterPayload {
  first_name: string
  email: string
  password: string
  password_confirmation: string
}

export interface ForgotPasswordPayload {
  email: string
}

export interface ResetPasswordPayload {
  token: string
  email: string
  password: string
  password_confirmation: string
}

export interface ApiResponse<T = null> {
  success: boolean
  message: string
  data?: T
  errors?: Record<string, string[]>
}

export interface AuthResponseData {
  user: AuthUser
}