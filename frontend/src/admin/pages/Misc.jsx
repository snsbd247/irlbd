import { useState } from 'react'
import { Link, Navigate, useLocation, useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Box, FileSignature, FileText, Inbox, Lock, Mail, Newspaper, Images, ShieldAlert } from 'lucide-react'
import { api, errorMessage, fieldErrors } from '../../lib/api'
import { formatDate } from '../../lib/utils'
import { ErrorState, Spinner } from '../../components/ui/States'
import { useAuth } from '../auth'
import { Badge, Card, PageHeader, SaveButton, statusTone, useToast } from '../components/ui'
import { useSettings } from '../../lib/queries'

export function NotAllowed() {
  return (
    <div className="flex flex-col items-center gap-3 py-20 text-center">
      <ShieldAlert className="h-10 w-10 text-amber-500" aria-hidden="true" />
      <h1 className="text-xl font-semibold text-navy-900">Access restricted</h1>
      <p className="max-w-sm text-sm text-muted">Your role does not include permission for this section. Ask a super admin if you need access.</p>
      <Link to="/admin" className="btn-outline mt-2">Back to dashboard</Link>
    </div>
  )
}

export function Login() {
  const { user, login } = useAuth()
  const { data: s = {} } = useSettings()
  const navigate = useNavigate()
  const location = useLocation()
  const [form, setForm] = useState({ email: '', password: '', remember: false })
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  if (user) return <Navigate to={location.state?.from || '/admin'} replace />

  async function submit(e) {
    e.preventDefault()
    setBusy(true)
    setError('')
    try {
      await login(form)
      navigate(location.state?.from || '/admin', { replace: true })
    } catch (err) {
      setError(fieldErrors(err).email || errorMessage(err, 'Login failed.'))
    }
    setBusy(false)
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-navy-950 via-navy-900 to-brand-800 px-4">
      <form onSubmit={submit} className="w-full max-w-sm rounded-2xl bg-white p-8 shadow-2xl" noValidate>
        <div className="mb-6 text-center">
          {s.logo_header_url ? <img src={s.logo_header_url} alt={s.site_name} className="mx-auto h-14 w-auto" /> : <p className="text-xl font-bold text-navy-900">{s.site_name || 'IR Limited'}</p>}
          <h1 className="mt-4 text-lg font-semibold text-navy-900">Admin sign in</h1>
        </div>
        {error && <p role="alert" className="mb-4 rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">{error}</p>}
        <div className="space-y-4">
          <div>
            <label htmlFor="login-email" className="label">Email</label>
            <input id="login-email" type="email" autoComplete="username" required className="input" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
          </div>
          <div>
            <label htmlFor="login-password" className="label">Password</label>
            <input id="login-password" type="password" autoComplete="current-password" required className="input" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} />
          </div>
          <label className="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" className="h-4 w-4 accent-brand-600" checked={form.remember} onChange={(e) => setForm({ ...form, remember: e.target.checked })} />
            Keep me signed in on this device
          </label>
          <button type="submit" className="btn-primary w-full py-3" disabled={busy || !form.email || !form.password}>
            {busy ? <Spinner className="h-4 w-4 text-white" label="Signing in" /> : <Lock className="h-4 w-4" aria-hidden="true" />} Sign in
          </button>
        </div>
        <p className="mt-6 text-center text-xs text-muted">
          <Link to="/" className="hover:text-brand-600">← Back to website</Link>
        </p>
      </form>
    </div>
  )
}

function Stat({ icon: IconComponent, label, value, sub, to }) {
  return (
    <Link to={to} className="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 hover:border-brand-200 hover:shadow-sm">
      <span className="icon-circle h-12 w-12"><IconComponent className="h-6 w-6" aria-hidden="true" /></span>
      <div>
        <p className="text-2xl font-bold text-navy-900">{value}</p>
        <p className="text-sm text-muted">{label}{sub ? ` · ${sub}` : ''}</p>
      </div>
    </Link>
  )
}

export function Dashboard() {
  const { user } = useAuth()
  const { data, isLoading, isError, refetch } = useQuery({ queryKey: ['admin', 'dashboard'], queryFn: () => api.get('/admin/dashboard').then((r) => r.data) })

  if (isLoading) return <div className="flex justify-center py-20"><Spinner /></div>
  if (isError) return <ErrorState onRetry={refetch} />
  const st = data.stats

  return (
    <div>
      <PageHeader title={`Welcome, ${user.name}`} description="Overview of your website content and enquiries." />
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        {st.quotes && <Stat icon={FileSignature} label="Quote requests" value={st.quotes.total} sub={`${st.quotes.new} new`} to="/admin/quote-requests" />}
        {st.enquiries && <Stat icon={Inbox} label="Enquiries" value={st.enquiries.total} sub={`${st.enquiries.new} new`} to="/admin/enquiries" />}
        {st.products && <Stat icon={Box} label="Products" value={st.products.total} sub={`${st.products.published} published`} to="/admin/r/products" />}
        {st.news && <Stat icon={Newspaper} label="News posts" value={st.news.total} sub={`${st.news.published} published`} to="/admin/r/news-posts" />}
        {st.pages && <Stat icon={FileText} label="Pages" value={st.pages.total} sub={`${st.pages.published} published`} to="/admin/r/pages" />}
        {st.subscribers && <Stat icon={Mail} label="Newsletter subscribers" value={st.subscribers.total} to="/admin/subscribers" />}
        {st.media && <Stat icon={Images} label="Media files" value={st.media.total} to="/admin/media" />}
      </div>
      <div className="mt-6 grid gap-6 lg:grid-cols-2">
        {data.recent_enquiries?.length > 0 && (
          <Card title="Latest enquiries" actions={<Link to="/admin/enquiries" className="text-sm font-medium text-brand-600">View all</Link>}>
            <ul className="divide-y divide-slate-100">
              {data.recent_enquiries.map((e) => (
                <li key={e.id} className="flex items-center justify-between gap-3 py-2.5 text-sm">
                  <div className="min-w-0">
                    <p className="truncate font-medium text-navy-900">{e.name} — {e.subject}</p>
                    <p className="text-xs text-muted">{e.email} · {formatDate(e.created_at)}</p>
                  </div>
                  <Badge tone={statusTone(e.status)}>{e.status}</Badge>
                </li>
              ))}
            </ul>
          </Card>
        )}
        {data.recent_news?.length > 0 && (
          <Card title="Recently updated news" actions={<Link to="/admin/r/news-posts" className="text-sm font-medium text-brand-600">View all</Link>}>
            <ul className="divide-y divide-slate-100">
              {data.recent_news.map((n) => (
                <li key={n.id} className="flex items-center justify-between gap-3 py-2.5 text-sm">
                  <Link to={`/admin/r/news-posts/${n.id}`} className="truncate font-medium text-navy-900 hover:text-brand-600">{n.title}</Link>
                  <Badge tone={statusTone(n.status)}>{n.status}</Badge>
                </li>
              ))}
            </ul>
          </Card>
        )}
      </div>
    </div>
  )
}

export function Profile() {
  const { user, setUser } = useAuth()
  const toast = useToast()
  const [form, setForm] = useState({ name: user.name, email: user.email, current_password: '', password: '', password_confirmation: '' })
  const [errors, setErrors] = useState({})
  const [saving, setSaving] = useState(false)
  const set = (k) => (e) => setForm({ ...form, [k]: e.target.value })

  async function submit(e) {
    e.preventDefault()
    setSaving(true)
    setErrors({})
    try {
      const { data } = await api.put('/admin/profile', form)
      setUser(data.user)
      setForm((f) => ({ ...f, current_password: '', password: '', password_confirmation: '' }))
      toast('Profile updated.')
    } catch (err) {
      setErrors(fieldErrors(err))
      toast(errorMessage(err), 'error')
    }
    setSaving(false)
  }

  const input = (key, label, type = 'text', autoComplete) => (
    <div>
      <label htmlFor={`profile-${key}`} className="label">{label}</label>
      <input id={`profile-${key}`} type={type} className="input" value={form[key]} onChange={set(key)} autoComplete={autoComplete} aria-invalid={!!errors[key]} />
      {errors[key] && <p className="mt-1 text-xs text-red-600">{errors[key]}</p>}
    </div>
  )

  return (
    <form onSubmit={submit} className="max-w-2xl space-y-6" noValidate>
      <PageHeader title="My profile" description={`Role: ${user.role}`} actions={<SaveButton saving={saving} />} />
      <Card title="Account">
        <div className="grid gap-4 sm:grid-cols-2">
          {input('name', 'Name')}
          {input('email', 'Email', 'email', 'username')}
        </div>
      </Card>
      <Card title="Change password">
        <div className="grid gap-4 sm:grid-cols-2">
          <div className="sm:col-span-2">{input('current_password', 'Current password', 'password', 'current-password')}</div>
          {input('password', 'New password', 'password', 'new-password')}
          {input('password_confirmation', 'Confirm new password', 'password', 'new-password')}
        </div>
        <p className="mt-3 text-xs text-muted">At least 10 characters including letters and numbers.</p>
      </Card>
    </form>
  )
}
