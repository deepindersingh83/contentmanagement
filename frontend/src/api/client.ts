import axios, { AxiosInstance, InternalAxiosRequestConfig } from 'axios'
import { useAuthStore } from '../store/authStore'

const API_BASE_URL = import.meta.env.VITE_API_URL || '/api'

export const apiClient: AxiosInstance = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

// Request interceptor - attach the JWT token.
apiClient.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = useAuthStore.getState().token
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error)
)

// Response interceptor - transparently refresh the token on a 401, once.
apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config

    if (error.response?.status === 401 && !originalRequest?._retry) {
      originalRequest._retry = true

      const refreshToken = useAuthStore.getState().refreshToken
      if (!refreshToken) {
        useAuthStore.getState().logout()
        redirectToLogin()
        return Promise.reject(error)
      }

      try {
        const response = await axios.post(`${API_BASE_URL}/auth/refresh`, {
          refresh_token: refreshToken,
        })
        const { token, refresh_token } = response.data
        useAuthStore.getState().setTokens(token, refresh_token)
        originalRequest.headers.Authorization = `Bearer ${token}`
        return apiClient(originalRequest)
      } catch (refreshError) {
        useAuthStore.getState().logout()
        redirectToLogin()
        return Promise.reject(refreshError)
      }
    }

    return Promise.reject(error)
  }
)

function redirectToLogin() {
  const base = import.meta.env.VITE_BASE_PATH || ''
  window.location.href = `${base}/login`
}

export default apiClient
