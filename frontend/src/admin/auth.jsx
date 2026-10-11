import { createContext, useCallback, useContext, useMemo } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { Navigate, useLocation } from 'react-router-dom'
import { api, fetchCsrfCookie } from '../lib/api'
import { PageLoader } from '../components/ui/States'

const AuthContext = createContext(null)

/**
 * Session-cookie auth (Laravel Sanctum SPA). The browser never sees or stores a token:
 * the session cookie is HttpOnly and every write carries the XSRF token header.
 */
export function AuthProvider({ children }) {
  const queryClient = useQueryClient()
  const { data: user, isLoading } = useQuery({
    queryKey: ['admin', 'me'],
    queryFn: () =>
      api
        .get('/admin/me')
        .then((r) => r.data.user)
        .catch((e) => {
          if ([401, 403, 419].includes(e.response?.status)) return null
          throw e
        }),
    staleTime: 5 * 60 * 1000,
    retry: false,
  })

  const login = useCallback(
    async (credentials) => {
      await fetchCsrfCookie()
      const { data } = await api.post('/admin/login', credentials)
      if (data.two_factor) return { twoFactor: true }
      queryClient.setQueryData(['admin', 'me'], data.user)
      return data.user
    },
    [queryClient],
  )

  const completeTwoFactor = useCallback(
    async (payload) => {
      const { data } = await api.post('/admin/two-factor-challenge', payload)
      queryClient.setQueryData(['admin', 'me'], data.user)
      return data.user
    },
    [queryClient],
  )

  const logout = useCallback(async () => {
    try {
      await api.post('/admin/logout')
    } finally {
      // Clear the signed-in user first (the auth observer stays attached to this query),
      // then drop every other cached admin response so nothing leaks to the next user.
      queryClient.setQueryData(['admin', 'me'], null)
      queryClient.removeQueries({ queryKey: ['admin'], predicate: (q) => q.queryKey[1] !== 'me' })
    }
  }, [queryClient])

  const value = useMemo(
    () => ({
      user,
      isLoading,
      login,
      completeTwoFactor,
      logout,
      can: (module) => !!user && (user.is_super || user.permissions.includes(`${module}.manage`)),
      setUser: (u) => queryClient.setQueryData(['admin', 'me'], u),
    }),
    [user, isLoading, login, completeTwoFactor, logout, queryClient],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export const useAuth = () => useContext(AuthContext)

export function RequireAuth({ children }) {
  const { user, isLoading } = useAuth()
  const location = useLocation()
  if (isLoading) return <PageLoader />
  if (!user) return <Navigate to="/admin/login" replace state={{ from: location.pathname + location.search }} />
  return children
}

export const useMeta = () =>
  useQuery({ queryKey: ['admin', 'meta'], queryFn: () => api.get('/admin/meta').then((r) => r.data), staleTime: 30 * 60 * 1000 })
