import { useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { CircleCheck, Copy, Download, KeyRound, Lock, ShieldCheck, ShieldOff, Smartphone } from 'lucide-react'
import { api, errorMessage, fieldErrors } from '../../lib/api'
import { cn, formatDate } from '../../lib/utils'
import { EmptyState, ErrorState, Spinner } from '../../components/ui/States'
import Pagination from '../../components/ui/Pagination'
import { useSettings } from '../../lib/queries'
import { useAuth } from '../auth'
import { Badge, Card, Modal, PageHeader, useToast } from '../components/ui'
import { NotAllowed } from './Misc'

function AuthShell({ title, children }) {
  const { data: s = {} } = useSettings()
  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-navy-950 via-navy-900 to-brand-800 px-4">
      <div className="w-full max-w-sm rounded-2xl bg-white p-8 shadow-2xl">
        <div className="mb-6 text-center">
          {s.logo_header_url ? <img src={s.logo_header_url} alt={s.site_name} className="mx-auto h-14 w-auto" /> : <p className="text-xl font-bold text-navy-900">{s.site_name || 'IR Limited'}</p>}
          <h1 className="mt-4 text-lg font-semibold text-navy-900">{title}</h1>
        </div>
        {children}
        <p className="mt-6 text-center text-xs text-muted">
          <Link to="/admin/login" className="hover:text-brand-600">← Back to sign in</Link>
        </p>
      </div>
    </div>
  )
}

/** Second login step: 6-digit authenticator code or a one-time recovery code. */
export function TwoFactorStep({ onDone, onCancel }) {
  const { completeTwoFactor } = useAuth()
  const [recovery, setRecovery] = useState(false)
  const [code, setCode] = useState('')
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  async function submit(e) {
    e.preventDefault()
    setBusy(true)
    setError('')
    try {
      await completeTwoFactor(recovery ? { recovery_code: code.trim() } : { code: code.replace(/\s/g, '') })
      onDone()
    } catch (err) {
      if (err.response?.status === 419) {
        onCancel('Your sign-in took too long. Please enter your password again.')
        return
      }
      setError(fieldErrors(err).code || errorMessage(err, 'Verification failed.'))
      setCode('')
    }
    setBusy(false)
  }

  return (
    <form onSubmit={submit} noValidate className="space-y-4">
      <div className="flex items-center gap-3 rounded-lg bg-brand-50 p-3 text-sm text-brand-800">
        <Smartphone className="h-5 w-5 shrink-0" aria-hidden="true" />
        {recovery ? 'Enter one of your saved recovery codes.' : 'Enter the 6-digit code from your authenticator app.'}
      </div>
      {error && <p role="alert" className="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">{error}</p>}
      <div>
        <label htmlFor="tfa-code" className="label">{recovery ? 'Recovery code' : 'Authentication code'}</label>
        <input
          id="tfa-code"
          className="input text-center text-lg tracking-[0.3em]"
          value={code}
          onChange={(e) => setCode(e.target.value)}
          inputMode={recovery ? 'text' : 'numeric'}
          autoComplete="one-time-code"
          maxLength={recovery ? 20 : 7}
          autoFocus
        />
      </div>
      <button type="submit" className="btn-primary w-full py-3" disabled={busy || code.trim().length < (recovery ? 8 : 6)}>
        {busy && <Spinner className="h-4 w-4 text-white" />} Verify
      </button>
      <div className="flex justify-between text-xs">
        <button type="button" className="text-brand-600 hover:underline" onClick={() => { setRecovery(!recovery); setCode(''); setError('') }}>
          {recovery ? 'Use authenticator code' : 'Use a recovery code'}
        </button>
        <button type="button" className="text-muted hover:underline" onClick={() => onCancel()}>Cancel</button>
      </div>
    </form>
  )
}

export function ForgotPassword() {
  const [email, setEmail] = useState('')
  const [state, setState] = useState({ status: 'idle', message: '' })

  async function submit(e) {
    e.preventDefault()
    setState({ status: 'loading', message: '' })
    try {
      const { data } = await api.post('/admin/forgot-password', { email })
      setState({ status: 'done', message: data.message })
    } catch (err) {
      setState({ status: 'error', message: fieldErrors(err).email || errorMessage(err) })
    }
  }

  return (
    <AuthShell title="Reset your password">
      {state.status === 'done' ? (
        <p role="status" className="flex gap-2 rounded-md bg-emerald-50 px-3 py-3 text-sm text-emerald-800">
          <CircleCheck className="h-5 w-5 shrink-0" aria-hidden="true" /> {state.message}
        </p>
      ) : (
        <form onSubmit={submit} noValidate className="space-y-4">
          <p className="text-sm text-muted">Enter your admin email address and we will send you a link to choose a new password.</p>
          {state.status === 'error' && <p role="alert" className="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">{state.message}</p>}
          <div>
            <label htmlFor="forgot-email" className="label">Email</label>
            <input id="forgot-email" type="email" className="input" value={email} onChange={(e) => setEmail(e.target.value)} autoComplete="username" required />
          </div>
          <button type="submit" className="btn-primary w-full py-3" disabled={state.status === 'loading' || !email}>
            {state.status === 'loading' && <Spinner className="h-4 w-4 text-white" />} Send reset link
          </button>
        </form>
      )}
    </AuthShell>
  )
}

export function ResetPassword() {
  const [params] = useSearchParams()
  const navigate = useNavigate()
  const token = params.get('token') || ''
  const email = params.get('email') || ''
  const [form, setForm] = useState({ password: '', password_confirmation: '' })
  const [errors, setErrors] = useState({})
  const [state, setState] = useState({ status: 'idle', message: '' })

  async function submit(e) {
    e.preventDefault()
    setErrors({})
    if (form.password !== form.password_confirmation) {
      setErrors({ password_confirmation: 'The passwords do not match.' })
      return
    }
    setState({ status: 'loading', message: '' })
    try {
      const { data } = await api.post('/admin/reset-password', { token, email, ...form })
      setState({ status: 'done', message: data.message })
      setTimeout(() => navigate('/admin/login', { replace: true }), 2500)
    } catch (err) {
      setErrors(fieldErrors(err))
      setState({ status: 'error', message: errorMessage(err) })
    }
  }

  if (!token || !email) {
    return <AuthShell title="Invalid link"><p className="text-sm text-muted">This password reset link is incomplete. Request a new one from the sign-in page.</p></AuthShell>
  }

  return (
    <AuthShell title="Choose a new password">
      {state.status === 'done' ? (
        <p role="status" className="flex gap-2 rounded-md bg-emerald-50 px-3 py-3 text-sm text-emerald-800"><CircleCheck className="h-5 w-5 shrink-0" aria-hidden="true" /> {state.message}</p>
      ) : (
        <form onSubmit={submit} noValidate className="space-y-4">
          <p className="text-sm text-muted">For <strong className="text-navy-900">{email}</strong>. At least 10 characters with letters and numbers.</p>
          {(errors.email || (state.status === 'error' && !errors.password)) && <p role="alert" className="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">{errors.email || state.message}</p>}
          <div>
            <label htmlFor="reset-password" className="label">New password</label>
            <input id="reset-password" type="password" className="input" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} autoComplete="new-password" />
            {errors.password && <p className="mt-1 text-xs text-red-600">{errors.password}</p>}
          </div>
          <div>
            <label htmlFor="reset-password2" className="label">Confirm new password</label>
            <input id="reset-password2" type="password" className="input" value={form.password_confirmation} onChange={(e) => setForm({ ...form, password_confirmation: e.target.value })} autoComplete="new-password" />
            {errors.password_confirmation && <p className="mt-1 text-xs text-red-600">{errors.password_confirmation}</p>}
          </div>
          <button type="submit" className="btn-primary w-full py-3" disabled={state.status === 'loading' || !form.password}>
            {state.status === 'loading' && <Spinner className="h-4 w-4 text-white" />} Save new password
          </button>
        </form>
      )}
    </AuthShell>
  )
}

function RecoveryCodes({ codes }) {
  const toast = useToast()
  const text = codes.join('\n')
  return (
    <div className="space-y-3">
      <p className="rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-800">
        Save these recovery codes somewhere safe. Each code can be used once to sign in if you lose your phone. They will not be shown again.
      </p>
      <ul className="grid grid-cols-2 gap-2 rounded-lg bg-slate-50 p-4 font-mono text-sm">
        {codes.map((c) => <li key={c}>{c}</li>)}
      </ul>
      <div className="flex gap-2">
        <button type="button" className="btn-outline py-2" onClick={() => navigator.clipboard?.writeText(text).then(() => toast('Codes copied.'))}><Copy className="h-4 w-4" aria-hidden="true" /> Copy</button>
        <a className="btn-outline py-2" href={`data:text/plain;charset=utf-8,${encodeURIComponent(text)}`} download="ir-limited-recovery-codes.txt"><Download className="h-4 w-4" aria-hidden="true" /> Download</a>
      </div>
    </div>
  )
}

/** Two-factor settings card shown on the profile page. */
export function TwoFactorCard() {
  const { user, setUser } = useAuth()
  const toast = useToast()
  const [mode, setMode] = useState(null) // 'enable' | 'confirm' | 'codes' | 'disable' | 'regenerate'
  const [password, setPassword] = useState('')
  const [code, setCode] = useState('')
  const [setup, setSetup] = useState(null)
  const [codes, setCodes] = useState([])
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  const close = () => { setMode(null); setPassword(''); setCode(''); setError(''); setSetup(null) }

  async function run(fn) {
    setBusy(true)
    setError('')
    try {
      await fn()
    } catch (err) {
      const f = fieldErrors(err)
      setError(f.password || f.code || errorMessage(err))
    }
    setBusy(false)
  }

  const start = () => run(async () => {
    const { data } = await api.post('/admin/two-factor', { password })
    setSetup(data)
    setPassword('')
    setMode('confirm')
  })
  const confirm = () => run(async () => {
    const { data } = await api.post('/admin/two-factor/confirm', { code })
    setCodes(data.recovery_codes)
    setUser({ ...user, two_factor_enabled: true })
    setMode('codes')
    toast('Two-factor authentication is now on.')
  })
  const disable = () => run(async () => {
    await api.delete('/admin/two-factor', { data: { password } })
    setUser({ ...user, two_factor_enabled: false })
    close()
    toast('Two-factor authentication turned off.')
  })
  const regenerate = () => run(async () => {
    const { data } = await api.post('/admin/two-factor/recovery-codes', { password })
    setCodes(data.recovery_codes)
    setPassword('')
    setMode('codes')
  })

  const passwordForm = (action, label, danger) => (
    <form onSubmit={(e) => { e.preventDefault(); action() }} className="space-y-3">
      <label htmlFor="tfa-password" className="label">Confirm with your password</label>
      <input id="tfa-password" type="password" className="input" value={password} onChange={(e) => setPassword(e.target.value)} autoComplete="current-password" autoFocus />
      {error && <p role="alert" className="text-sm text-red-600">{error}</p>}
      <button type="submit" className={cn('btn w-full', danger ? 'bg-red-600 text-white hover:bg-red-700' : 'btn-primary')} disabled={busy || !password}>
        {busy && <Spinner className="h-4 w-4 text-white" />} {label}
      </button>
    </form>
  )

  return (
    <Card title="Two-factor authentication (2FA)">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex items-start gap-3">
          {user.two_factor_enabled ? <ShieldCheck className="h-8 w-8 shrink-0 text-emerald-600" aria-hidden="true" /> : <ShieldOff className="h-8 w-8 shrink-0 text-slate-400" aria-hidden="true" />}
          <div>
            <p className="font-medium text-navy-900">{user.two_factor_enabled ? '2FA is on' : '2FA is off'}</p>
            <p className="text-sm text-muted">Protect your account with a 6-digit code from an authenticator app (Google Authenticator, Microsoft Authenticator, Authy) in addition to your password.</p>
          </div>
        </div>
        <div className="flex shrink-0 gap-2">
          {user.two_factor_enabled ? (
            <>
              <button type="button" className="btn-outline py-2" onClick={() => setMode('regenerate')}><KeyRound className="h-4 w-4" aria-hidden="true" /> New recovery codes</button>
              <button type="button" className="btn bg-red-50 py-2 text-red-700 hover:bg-red-100" onClick={() => setMode('disable')}>Turn off</button>
            </>
          ) : (
            <button type="button" className="btn-primary py-2" onClick={() => setMode('enable')}><Lock className="h-4 w-4" aria-hidden="true" /> Turn on 2FA</button>
          )}
        </div>
      </div>

      <Modal open={!!mode} onClose={close} size="md" title={{ enable: 'Turn on two-factor authentication', confirm: 'Scan the QR code', codes: 'Your recovery codes', disable: 'Turn off two-factor authentication', regenerate: 'New recovery codes' }[mode]}>
        {mode === 'enable' && passwordForm(start, 'Continue')}
        {mode === 'disable' && passwordForm(disable, 'Turn off 2FA', true)}
        {mode === 'regenerate' && passwordForm(regenerate, 'Generate new codes (old ones stop working)')}
        {mode === 'confirm' && setup && (
          <form onSubmit={(e) => { e.preventDefault(); confirm() }} className="space-y-4">
            <ol className="list-decimal space-y-1 pl-5 text-sm text-slate-700">
              <li>Open your authenticator app and add a new account.</li>
              <li>Scan this QR code (or type the key below).</li>
              <li>Enter the 6-digit code the app shows.</li>
            </ol>
            <img src={`data:image/svg+xml;base64,${btoa(setup.qr_svg)}`} alt="QR code for your authenticator app" className="mx-auto h-52 w-52 rounded-lg border border-slate-200 bg-white p-2" />
            <p className="text-center text-xs text-muted">Key: <code className="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] break-all text-navy-900">{setup.secret}</code></p>
            <div>
              <label htmlFor="tfa-confirm" className="label">6-digit code</label>
              <input id="tfa-confirm" className="input text-center text-lg tracking-[0.3em]" value={code} onChange={(e) => setCode(e.target.value)} inputMode="numeric" autoComplete="one-time-code" maxLength={7} autoFocus />
            </div>
            {error && <p role="alert" className="text-sm text-red-600">{error}</p>}
            <button type="submit" className="btn-primary w-full" disabled={busy || code.replace(/\s/g, '').length !== 6}>{busy && <Spinner className="h-4 w-4 text-white" />} Confirm & turn on</button>
          </form>
        )}
        {mode === 'codes' && (
          <div className="space-y-4">
            <RecoveryCodes codes={codes} />
            <button type="button" className="btn-primary w-full" onClick={close}>I have saved my codes</button>
          </div>
        )}
      </Modal>
    </Card>
  )
}

const ACTION_TONES = { created: 'green', updated: 'blue', deleted: 'red', login: 'slate', logout: 'slate', login_failed: 'red', '2fa_failed': 'red', cv_downloaded: 'amber', exported: 'amber' }

function Changes({ changes }) {
  if (!changes) return null
  return (
    <ul className="mt-1 space-y-0.5 text-xs text-slate-600">
      {Object.entries(changes).map(([field, c]) => (
        <li key={field}>
          <span className="font-medium text-navy-900">{field}</span>:{' '}
          {c.changed ? 'changed' : (
            <>
              <span className="text-red-600 line-through">{String(c.from ?? '∅')}</span> → <span className="text-emerald-700">{String(c.to ?? '∅')}</span>
            </>
          )}
        </li>
      ))}
    </ul>
  )
}

export function ActivityLogPage() {
  const { can } = useAuth()
  const [page, setPage] = useState(1)
  const [action, setAction] = useState('')
  const [search, setSearch] = useState('')
  const queryClient = useQueryClient()
  const params = { page, action: action || undefined, search: search || undefined }
  const { data, isLoading, isError, refetch } = useQuery({
    queryKey: ['admin', 'activity-log', params],
    queryFn: () => api.get('/admin/activity-log', { params }).then((r) => r.data),
    placeholderData: (prev) => prev,
    enabled: can('users'),
  })

  if (!can('users')) return <NotAllowed />

  return (
    <div>
      <PageHeader title="Activity log" description="Who changed what, and when. Entries are kept for 12 months and cannot be edited." actions={<button type="button" className="btn-outline" onClick={() => queryClient.invalidateQueries({ queryKey: ['admin', 'activity-log'] })}>Refresh</button>} />
      <Card>
        <div className="mb-4 flex flex-col gap-2 sm:flex-row">
          <input type="search" aria-label="Search activity" className="input flex-1" placeholder="Search item, user or IP…" value={search} onChange={(e) => { setSearch(e.target.value); setPage(1) }} />
          <select aria-label="Action" className="input sm:w-52" value={action} onChange={(e) => { setAction(e.target.value); setPage(1) }}>
            <option value="">All actions</option>
            {['created', 'updated', 'deleted', 'login', 'login_failed', 'logout', 'password_changed', 'password_reset', '2fa_enabled', '2fa_disabled', '2fa_failed', 'cv_downloaded', 'exported'].map((a) => <option key={a} value={a}>{a.replace(/_/g, ' ')}</option>)}
          </select>
        </div>
        {isLoading ? <div className="flex justify-center py-12"><Spinner /></div> : isError ? <ErrorState onRetry={refetch} /> : !data?.data?.length ? (
          <EmptyState title="No activity yet" />
        ) : (
          <div className="-mx-5 overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-y border-slate-100 bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase">
                  <th className="px-5 py-2.5">When</th><th className="px-3 py-2.5">User</th><th className="px-3 py-2.5">Action</th><th className="px-3 py-2.5">Item</th><th className="px-5 py-2.5">IP</th>
                </tr>
              </thead>
              <tbody>
                {data.data.map((row) => (
                  <tr key={row.id} className="border-b border-slate-100 align-top">
                    <td className="px-5 py-2.5 whitespace-nowrap text-slate-600">{formatDate(row.created_at)}<span className="block text-xs text-muted">{new Date(row.created_at).toLocaleTimeString()}</span></td>
                    <td className="px-3 py-2.5 text-navy-900">{row.user_name || <span className="text-muted">—</span>}</td>
                    <td className="px-3 py-2.5"><Badge tone={ACTION_TONES[row.action] || 'slate'}>{row.action.replace(/_/g, ' ')}</Badge></td>
                    <td className="px-3 py-2.5">
                      <span className="text-navy-900">{row.subject_label || '—'}</span>
                      {row.subject_type && <span className="ml-1 text-xs text-muted">({row.subject_type})</span>}
                      <Changes changes={row.changes} />
                    </td>
                    <td className="px-5 py-2.5 font-mono text-xs text-muted">{row.ip_address}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
        <Pagination meta={data?.meta} onChange={setPage} className="mt-4" />
      </Card>
    </div>
  )
}
