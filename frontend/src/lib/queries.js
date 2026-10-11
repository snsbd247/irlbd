import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { api } from './api'

const get = (url, params) => api.get(url, { params }).then((r) => r.data)

export const useSettings = () => useQuery({ queryKey: ['settings'], queryFn: () => get('/settings'), staleTime: 5 * 60 * 1000 })

export const useNavigation = () => useQuery({ queryKey: ['navigation'], queryFn: () => get('/navigation'), staleTime: 5 * 60 * 1000 })

export const useHomePage = () => useQuery({ queryKey: ['page', '__home'], queryFn: () => get('/home') })

export const usePage = (slug) => useQuery({ queryKey: ['page', slug], queryFn: () => get(`/pages/${encodeURIComponent(slug)}`), enabled: !!slug })

export const useProducts = (params) =>
  useQuery({ queryKey: ['products', params], queryFn: () => get('/products', params), placeholderData: keepPreviousData })

export const useProduct = (slug) => useQuery({ queryKey: ['product', slug], queryFn: () => get(`/products/${encodeURIComponent(slug)}`) })

export const useProductCategories = () => useQuery({ queryKey: ['product-categories'], queryFn: () => get('/product-categories'), staleTime: 5 * 60 * 1000 })

export const useNews = (params) => useQuery({ queryKey: ['news', params], queryFn: () => get('/news', params), placeholderData: keepPreviousData })

export const useNewsPost = (slug) => useQuery({ queryKey: ['news-post', slug], queryFn: () => get(`/news/${encodeURIComponent(slug)}`) })

export const useNewsCategories = () => useQuery({ queryKey: ['news-categories'], queryFn: () => get('/news-categories'), staleTime: 5 * 60 * 1000 })

export const useRecentNews = () => useQuery({ queryKey: ['news-recent'], queryFn: () => get('/news/recent'), staleTime: 5 * 60 * 1000 })

export const useService = (slug) => useQuery({ queryKey: ['service', slug], queryFn: () => get(`/services/${encodeURIComponent(slug)}`) })

export const useContactInfo = () => useQuery({ queryKey: ['contact-info'], queryFn: () => get('/contact-info'), staleTime: 5 * 60 * 1000 })

export const useSearch = (q) => useQuery({ queryKey: ['search', q], queryFn: () => get('/search', { q }), enabled: (q || '').length >= 2 })
