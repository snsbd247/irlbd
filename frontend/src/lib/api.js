import axios from 'axios'

/**
 * API host. Empty in development (Vite proxies /api to Laravel) or when the API is
 * served from the same domain. In production set VITE_API_URL=https://api.example.com.
 */
export const API_ORIGIN = (import.meta.env.VITE_API_URL || '').replace(/\/$/, '')

export const api = axios.create({
  baseURL: `${API_ORIGIN}/api/v1`,
  // Session cookie + XSRF cookie (Laravel Sanctum SPA auth). Nothing is kept in localStorage.
  withCredentials: true,
  withXSRFToken: true,
  headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
  timeout: 30000,
})

// Current website language for public content requests (admin requests are never translated).
let apiLanguage = 'en'
export function setApiLanguage(lang) {
  apiLanguage = lang
}
api.interceptors.request.use((config) => {
  const method = (config.method || 'get').toLowerCase()
  if (method === 'get' && apiLanguage !== 'en' && !String(config.url || '').startsWith('/admin')) {
    config.params = { ...(config.params || {}), lang: apiLanguage }
  }
  return config
})

const hasXsrfCookie = () => document.cookie.split('; ').some((c) => c.startsWith('XSRF-TOKEN='))

let csrfPromise = null
export function fetchCsrfCookie() {
  csrfPromise ??= axios
    .get(`${API_ORIGIN}/sanctum/csrf-cookie`, { withCredentials: true })
    .finally(() => {
      csrfPromise = null
    })
  return csrfPromise
}

// State-changing requests need the XSRF-TOKEN cookie first.
api.interceptors.request.use(async (config) => {
  const method = (config.method || 'get').toLowerCase()
  if (!['get', 'head', 'options'].includes(method) && !hasXsrfCookie()) {
    await fetchCsrfCookie()
  }
  return config
})

// An expired CSRF token (419) is refreshed and the request retried once.
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const { config, response } = error
    if (response?.status === 419 && config && !config.__retried) {
      config.__retried = true
      await fetchCsrfCookie()
      return api(config)
    }
    return Promise.reject(error)
  },
)

/** Human-friendly message from an API error. */
export function errorMessage(error, fallback = 'Something went wrong. Please try again.') {
  if (!error?.response) return 'Network error. Please check your connection and try again.'
  const { status, data } = error.response
  if (status === 429) return 'Too many attempts. Please wait a minute and try again.'
  if (status === 403) return data?.message || 'You do not have permission to do this.'
  if (status === 404) return 'The requested item was not found.'
  if (status >= 500) return fallback
  return data?.message || fallback
}

/** Field errors from a 422 response: { field: 'first message' } */
export function fieldErrors(error) {
  const errors = error?.response?.status === 422 ? error.response.data?.errors || {} : {}
  return Object.fromEntries(Object.entries(errors).map(([key, messages]) => [key, messages?.[0]]))
}
