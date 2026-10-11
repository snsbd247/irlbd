import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { CircleCheck, ClipboardList, PackageCheck, Search, Send } from 'lucide-react'
import Seo from '../components/Seo'
import PageHero from '../components/layout/PageHero'
import { Spinner } from '../components/ui/States'
import { api, errorMessage, fieldErrors } from '../lib/api'
import { cn, formatDate } from '../lib/utils'
import { useT } from '../lib/i18n'

const STEPS = [
  ['new', 'Received', ClipboardList],
  ['quoted', 'Quotation sent', Send],
  ['ordered', 'Order confirmed', PackageCheck],
]

/** Public status lookup for a quotation request: reference + the email used on the form. */
export default function TrackQuotePage() {
  const t = useT()
  const [params] = useSearchParams()
  const [form, setForm] = useState({ reference: params.get('ref') || '', email: '' })
  const [errors, setErrors] = useState({})
  const [state, setState] = useState({ status: 'idle', result: null, message: '' })

  async function submit(e) {
    e.preventDefault()
    setErrors({})
    setState({ status: 'loading', result: null, message: '' })
    try {
      const { data } = await api.post('/quote/track', { reference: form.reference.trim(), email: form.email.trim() })
      setState({ status: 'done', result: data, message: '' })
    } catch (error) {
      setErrors(fieldErrors(error))
      setState({ status: 'error', result: null, message: errorMessage(error, 'We could not find a quotation request with this reference and email address.') })
    }
  }

  const r = state.result
  const current = r ? STEPS.findIndex(([key]) => key === r.status) : -1

  return (
    <>
      <Seo title={t('Track your quotation')} noindex />
      <PageHero title={t('Track your quotation')} subtitle={t('Enter the reference number from your confirmation email.')} crumbs={[{ label: t('Request a Quote'), href: '/request-quote' }, { label: t('Track') }]} />
      <section className="py-12">
        <div className="container-x grid max-w-4xl gap-8 lg:grid-cols-[340px_1fr]">
          <form onSubmit={submit} noValidate className="card h-fit space-y-4 p-6">
            <div>
              <label htmlFor="track-reference" className="label">{t('Reference number')}</label>
              <input id="track-reference" className={cn('input uppercase', errors.reference && 'border-red-400')} placeholder="IRQ-2026-00012" value={form.reference} onChange={(e) => setForm({ ...form, reference: e.target.value })} maxLength={20} autoComplete="off" required />
              {errors.reference && <p className="mt-1 text-xs text-red-600">{errors.reference}</p>}
            </div>
            <div>
              <label htmlFor="track-email" className="label">{t('Email')}</label>
              <input id="track-email" type="email" className={cn('input', errors.email && 'border-red-400')} value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} maxLength={150} autoComplete="email" required />
              {errors.email && <p className="mt-1 text-xs text-red-600">{errors.email}</p>}
            </div>
            <button type="submit" className="btn-primary w-full py-3" disabled={state.status === 'loading'}>
              {state.status === 'loading' ? <Spinner className="h-4 w-4 text-white" label="Searching" /> : <Search className="h-4 w-4" aria-hidden="true" />} {t('Check status')}
            </button>
          </form>

          <div aria-live="polite">
            {state.status === 'error' && <p role="alert" className="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{t(state.message)}</p>}
            {state.status === 'idle' && (
              <p className="text-sm text-muted">
                {t('Do not have a reference number?')} <Link to="/request-quote" className="font-semibold text-brand-600">{t('Request a Quote')}</Link>
              </p>
            )}
            {r && (
              <div className="card p-6">
                <p className="text-xs font-semibold tracking-wide text-muted uppercase">{r.reference}</p>
                <h2 className="mt-1 text-xl font-semibold text-navy-900">{r.product}</h2>
                <p className="text-sm text-muted">{t('Quantity')}: {r.quantity} · {t('Submitted')} {formatDate(r.submitted_at)}</p>

                {r.status === 'closed' ? (
                  <p className="mt-6 rounded-lg bg-slate-100 px-4 py-3 text-sm text-slate-700">{t('This request is closed.')}</p>
                ) : (
                  <ol className="mt-6 grid gap-3 sm:grid-cols-3">
                    {STEPS.map(([key, label, IconComponent], i) => (
                      <li key={key} className={cn('flex items-center gap-3 rounded-lg border p-3 text-sm', i <= current ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-200 text-slate-400')}>
                        {i < current ? <CircleCheck className="h-5 w-5 shrink-0" aria-hidden="true" /> : <IconComponent className="h-5 w-5 shrink-0" aria-hidden="true" />}
                        <span className={cn(i === current && 'font-semibold')}>{t(label)}</span>
                      </li>
                    ))}
                  </ol>
                )}

                {r.customer_note && (
                  <div className="mt-5 rounded-lg bg-brand-50 px-4 py-3 text-sm">
                    <p className="text-xs font-semibold text-muted uppercase">{t('Message from our team')}</p>
                    <p className="mt-1 whitespace-pre-line text-navy-900">{r.customer_note}</p>
                  </div>
                )}
                <p className="mt-4 text-xs text-muted">{t('Last updated')}: {formatDate(r.updated_at)}</p>
              </div>
            )}
          </div>
        </div>
      </section>
    </>
  )
}
