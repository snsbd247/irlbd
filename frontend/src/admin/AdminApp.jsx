import { Navigate, Route, Routes, useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { api } from '../lib/api'
import { Spinner } from '../components/ui/States'
import { AuthProvider, RequireAuth } from './auth'
import { ConfirmProvider, ToastProvider } from './components/ui'
import AdminLayout from './AdminLayout'
import { RESOURCES } from './resources'
import { ResourceForm, ResourceList } from './pages/ResourcePages'
import PageEditor from './pages/PageEditor'
import NewsletterEditor from './pages/Newsletter'
import Menus from './pages/Menus'
import Settings from './pages/Settings'
import { Enquiries, JobApplications, MediaLibrary, QuoteRequests, Subscribers } from './pages/Inbox'
import { Dashboard, Login, Profile } from './pages/Misc'
import { ActivityLogPage, ForgotPassword, ResetPassword } from './pages/Security'

function ResourceRoute({ form = false }) {
  const { resource, id } = useParams()
  if (!RESOURCES[resource]) return <Navigate to="/admin" replace />
  if (resource === 'pages' && form) return <Navigate to={`/admin/pages/${id}`} replace />
  if (resource === 'newsletter-campaigns' && form) return <Navigate to={`/admin/newsletters/${id}`} replace />
  return form ? <ResourceForm key={resource} resourceKey={resource} /> : <ResourceList key={resource} resourceKey={resource} />
}

/** Shortcut to the page builder of whichever page is the homepage. */
function HomepageBuilder() {
  const { data, isLoading } = useQuery({
    queryKey: ['admin', 'pages', 'home-lookup'],
    queryFn: () => api.get('/admin/pages', { params: { all: 1 } }).then((r) => r.data.find((p) => p.is_home)),
  })
  if (isLoading) return <div className="flex justify-center py-20"><Spinner /></div>
  return data ? <Navigate to={`/admin/pages/${data.id}`} replace /> : <Navigate to="/admin/r/pages" replace />
}

export default function AdminApp() {
  return (
    <ToastProvider>
      <ConfirmProvider>
        <AuthProvider>
          <Routes>
            <Route path="login" element={<Login />} />
            <Route path="forgot-password" element={<ForgotPassword />} />
            <Route path="reset-password" element={<ResetPassword />} />
            <Route
              element={
                <RequireAuth>
                  <AdminLayout />
                </RequireAuth>
              }
            >
              <Route index element={<Dashboard />} />
              <Route path="homepage" element={<HomepageBuilder />} />
              <Route path="pages/:id" element={<PageEditor />} />
              <Route path="newsletters/:id" element={<NewsletterEditor />} />
              <Route path="menus" element={<Menus />} />
              <Route path="settings" element={<Settings />} />
              <Route path="media" element={<MediaLibrary />} />
              <Route path="enquiries" element={<Enquiries />} />
              <Route path="subscribers" element={<Subscribers />} />
              <Route path="quote-requests" element={<QuoteRequests />} />
              <Route path="job-applications" element={<JobApplications />} />
              <Route path="profile" element={<Profile />} />
              <Route path="activity-log" element={<ActivityLogPage />} />
              <Route path="r/:resource" element={<ResourceRoute />} />
              <Route path="r/:resource/:id" element={<ResourceRoute form />} />
              <Route path="*" element={<Navigate to="/admin" replace />} />
            </Route>
          </Routes>
        </AuthProvider>
      </ConfirmProvider>
    </ToastProvider>
  )
}
