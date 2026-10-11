import { lazy, Suspense } from 'react'
import { Navigate, Route, Routes } from 'react-router-dom'
import PublicLayout from './components/layout/PublicLayout'
import { PageLoader } from './components/ui/States'
import ErrorBoundary from './components/ui/ErrorBoundary'
import QuotePage from './pages/QuotePage'
import TrackQuotePage from './pages/TrackQuotePage'
import CatalogPage from './pages/CatalogPage'
import ComparePage from './pages/ComparePage'
import { AlbumPage } from './pages/GalleryPages'
import { JobDetailPage } from './pages/CareerPages'
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
        <Route path="request-quote" element={<QuotePage />} />
        <Route path="track-quote" element={<TrackQuotePage />} />
        <Route path="catalog" element={<CatalogPage />} />
        {/* The branches page was briefly published as /dealers. */}
        <Route path="dealers" element={<Navigate to="/branches" replace />} />
        <Route path="compare" element={<ComparePage />} />
        <Route path="gallery/:slug" element={<AlbumPage />} />
        <Route path="careers/:slug" element={<JobDetailPage />} />
        <Route path="newsletter/unsubscribe" element={<UnsubscribePage />} />
        <Route path=":slug" element={<DynamicPage />} />
        <Route path="*" element={<NotFound />} />
      </Route>
    </Routes>
  )
}
