import apiClient from './client'

export interface LoginCredentials {
  email: string
  password: string
}

export interface AuthResponse {
  token: string
  refresh_token: string
}

export interface User {
  id: number
  name: string
  email: string
  phone?: string | null
  jobTitle?: string | null
  roles: string[]
}

export interface ProfileUpdate {
  name?: string
  email?: string
  phone?: string
  jobTitle?: string
}

export interface PasswordChange {
  currentPassword: string
  newPassword: string
}

export const authApi = {
  login: async (credentials: LoginCredentials): Promise<AuthResponse> => {
    const response = await apiClient.post<AuthResponse>('/auth/login', credentials)
    return response.data
  },

  me: async (): Promise<User> => {
    const response = await apiClient.get<User>('/auth/me')
    return response.data
  },

  updateProfile: async (payload: ProfileUpdate): Promise<User> => {
    const response = await apiClient.patch<User>('/profile', payload)
    return response.data
  },

  changePassword: async (payload: PasswordChange): Promise<{ message: string }> => {
    const response = await apiClient.put<{ message: string }>('/profile/password', payload)
    return response.data
  },
}
