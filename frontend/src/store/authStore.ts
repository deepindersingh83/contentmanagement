import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import type { User } from '../api/auth'

interface AuthState {
  user: User | null
  token: string | null
  refreshToken: string | null
  isAuthenticated: boolean
  setUser: (user: User) => void
  setTokens: (token: string, refreshToken: string) => void
  login: (user: User, token: string, refreshToken: string) => void
  logout: () => void
  hasRole: (role: string) => boolean
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      refreshToken: null,
      isAuthenticated: false,

      setUser: (user) => set({ user }),

      setTokens: (token, refreshToken) => set({ token, refreshToken }),

      login: (user, token, refreshToken) =>
        set({ user, token, refreshToken, isAuthenticated: true }),

      logout: () =>
        set({ user: null, token: null, refreshToken: null, isAuthenticated: false }),

      hasRole: (role: string) => {
        const user = get().user
        if (!user) return false
        if (user.roles.includes(role)) return true
        // ROLE_ADMIN implies ROLE_USER (mirrors the backend role hierarchy).
        if (role === 'ROLE_USER' && user.roles.includes('ROLE_ADMIN')) return true
        return false
      },
    }),
    {
      name: 'cms-auth-storage',
      partialize: (state) => ({
        user: state.user,
        token: state.token,
        refreshToken: state.refreshToken,
        isAuthenticated: state.isAuthenticated,
      }),
    }
  )
)
