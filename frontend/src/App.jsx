import { lazy, Suspense } from 'react'
import { Route, Routes } from 'react-router-dom'
import PublicLayout from './components/layout/PublicLayout'
import { PageLoader } from './components/ui/States'
import ErrorBoundary from './components/ui/ErrorBoundary'
import { DynamicPage, HomePage, NewsDetail, NotFound, ProductDetail, SearchPage, ServiceDetail, UnsubscribePage } from './pages/PublicPages'

// The admin CMS is code-split so public visitors never download it.
const AdminApp = lazy(() => import('./admin/AdminApp'))

export default function App() {
  return (
    <Routes>
      <Route
        path="/admin/*"
        element={
          <ErrorBoundary>
            <Suspense fallback={<PageLoader />}>
              <AdminApp />
            </Suspense>
          </ErrorBoundary>
        }
      />
      <Route
        element={
          <ErrorBoundary>
            <PublicLayout />
          </ErrorBoundary>
        }
      >
        <Route index element={<HomePage />} />
        <Route path="products/:slug" element={<ProductDetail />} />
        <Route path="news/:slug" element={<NewsDetail />} />
        <Route path="services/:slug" element={<ServiceDetail />} />
        <Route path="search" element={<SearchPage />} />
        <Route path="newsletter/unsubscribe" element={<UnsubscribePage />} />
        <Route path=":slug" element={<DynamicPage />} />
        <Route path="*" element={<NotFound />} />
      </Route>
    </Routes>
  )
}
