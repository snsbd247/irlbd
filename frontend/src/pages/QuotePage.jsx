import { useMemo, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ArrowRight, CircleCheck, Clock, PackageCheck, Truck } from 'lucide-react'
import Seo from '../components/Seo'
import PageHero from '../components/layout/PageHero'
import { Spinner } from '../components/ui/States'
import { api, errorMessage, fieldErrors } from '../lib/api'
import { cn } from '../lib/utils'
import { translate, useT } from '../lib/i18n'

// product_id: null = not chosen yet (use the ?product= preselection), '' = custom / not listed.
const EMPTY = { product_id: null, quantity: '', specification: '', needed_by: '', name: '', company: '', email: '', phone: '', message: '', website: '' }

function validate(f) {
  const e = {}
  if (f.name.trim().length < 2) e.name = translate('Please enter your name.')
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.email.trim())) e.email = translate('Please enter a valid email address.')
  if (!/^[0-9+\-\s()]{6,30}$/.test(f.phone.trim())) e.phone = translate('Please enter a valid phone number.')
  if (!f.quantity.trim()) e.quantity = translate('Please tell us the quantity you need.')
  if (f.needed_by && f.needed_by < new Date().toISOString().slice(0, 10)) e.needed_by = 'The date cannot be in the past.'
  return e
}

function Field({ id, label, required, error, hint, children, className }) {
  return (
    <div className={className}>
      <label htmlFor={id} className="label">
        {label} {required && <span className="text-red-500" aria-hidden="true">*</span>}
      </label>
      {children}
      {hint && !error && <p className="mt-1 text-xs text-muted">{hint}</p>}
      {error && <p id={`${id}-error`} className="mt-1 text-xs text-red-600">{error}</p>}
    </div>
  )
}

export default function QuotePage() {
  const t = useT()
  const [params] = useSearchParams()
  const { data: products } = useQuery({
    queryKey: ['products', 'quote-options'],
    queryFn: () => api.get('/products', { params: { per_page: 48 } }).then((r) => r.data.data),
    staleTime: 5 * 60 * 1000,
  })
  const preselected = useMemo(() => products?.find((p) => p.slug === params.get('product'))?.id, [products, params])
  const [form, setForm] = useState(EMPTY)
  const [errors, setErrors] = useState({})
  const [status, setStatus] = useState({ state: 'idle', message: '' })
  const productId = form.product_id ?? (preselected ? String(preselected) : '')
  const set = (k) => (e) => setForm((f) => ({ ...f, [k]: e.target.value }))
  const aria = (k) => ({ 'aria-invalid': !!errors[k], 'aria-describedby': errors[k] ? `quote-${k}-error` : undefined })

  async function submit(e) {
    e.preventDefault()
    const v = validate(form)
    setErrors(v)
    if (Object.keys(v).length) {
      document.getElementById(`quote-${Object.keys(v)[0]}`)?.focus()
      return
    }
    setStatus({ state: 'loading', message: '' })
    try {
      const payload = { ...form, product_id: productId ? Number(productId) : null, needed_by: form.needed_by || null }
      const { data } = await api.post('/quote', payload)
      setStatus({ state: 'success', message: data.message, reference: data.reference })
      setForm(EMPTY)
    } catch (error) {
      setErrors(fieldErrors(error))
      setStatus({ state: 'error', message: errorMessage(error, 'Your request could not be sent. Please try again or contact us directly.') })
    }
  }

  return (
    <>
      <Seo title={t('Request a Quote')} description="Request a quotation for PET & HDPE bottles, caps, closures, jars and containers from IR Limited." />
      <PageHero title={t('Request a Quote')} subtitle={t('Tell us what you need and our sales team will send you a quotation.')} crumbs={[{ label: t('Request a Quote') }]} />
      <section className="py-12">
        <div className="container-x grid gap-8 lg:grid-cols-[1fr_320px]">
          <div className="rounded-2xl bg-brand-50/70 p-5 sm:p-8">
            {status.state === 'success' ? (
              <div role="status" className="flex flex-col items-center gap-3 rounded-xl bg-white px-6 py-14 text-center">
                <CircleCheck className="h-12 w-12 text-emerald-600" aria-hidden="true" />
                <h2 className="text-xl font-semibold text-navy-900">{t('Request received')}</h2>
                <p className="max-w-md text-sm text-muted">{status.message}</p>
                {status.reference && (
                  <p className="rounded-lg border border-brand-100 bg-brand-50 px-4 py-3 text-sm text-navy-900">
                    {t('Your reference number')}: <strong className="font-semibold tracking-wide">{status.reference}</strong>
                    <span className="mt-1 block text-xs text-muted">{t('We have also emailed it to you. Use it to track your request.')}</span>
                  </p>
                )}
                <div className="mt-2 flex gap-3">
                  <button type="button" className="btn-outline" onClick={() => setStatus({ state: 'idle', message: '' })}>{t('Request another quote')}</button>
                  {status.reference ? (
                    <Link to={`/track-quote?ref=${encodeURIComponent(status.reference)}`} className="btn-primary">{t('Track my request')}</Link>
                  ) : (
                    <Link to="/products" className="btn-primary">{t('Browse products')}</Link>
                  )}
                </div>
              </div>
            ) : (
              <form onSubmit={submit} noValidate className="space-y-6">
                <fieldset className="space-y-4 rounded-xl bg-white p-5">
                  <legend className="px-1 text-base font-semibold text-navy-900">{t('What do you need?')}</legend>
                  <div className="grid gap-4 sm:grid-cols-2">
                    <Field id="quote-product_id" label={t('Product')} className="sm:col-span-2" hint="Leave empty for custom packaging.">
                      <select id="quote-product_id" className="input" value={productId} onChange={set('product_id')}>
                        <option value="">{t('Custom / not listed')}</option>
                        {(products || []).map((p) => (
                          <option key={p.id} value={p.id}>{p.name}{p.category ? ` — ${p.category.name}` : ''}</option>
                        ))}
                      </select>
                    </Field>
                    <Field id="quote-quantity" label={t('Quantity')} required error={errors.quantity} hint="e.g. 50,000 pcs">
                      <input id="quote-quantity" className={cn('input', errors.quantity && 'border-red-400')} value={form.quantity} onChange={set('quantity')} maxLength={60} {...aria('quantity')} />
                    </Field>
                    <Field id="quote-needed_by" label={t('Needed by')} error={errors.needed_by}>
                      <input id="quote-needed_by" type="date" className={cn('input', errors.needed_by && 'border-red-400')} value={form.needed_by} min={new Date().toISOString().slice(0, 10)} onChange={set('needed_by')} {...aria('needed_by')} />
                    </Field>
                    <Field id="quote-specification" label={t('Size, colour & specification')} className="sm:col-span-2" error={errors.specification} hint="e.g. 500 ml, clear PET, 28 mm neck, blue cap, printed label">
                      <input id="quote-specification" className="input" value={form.specification} onChange={set('specification')} maxLength={500} {...aria('specification')} />
                    </Field>
                  </div>
                </fieldset>

                <fieldset className="space-y-4 rounded-xl bg-white p-5">
                  <legend className="px-1 text-base font-semibold text-navy-900">{t('Your details')}</legend>
                  <div className="grid gap-4 sm:grid-cols-2">
                    <Field id="quote-name" label={t('Your Name')} required error={errors.name}>
                      <input id="quote-name" className={cn('input', errors.name && 'border-red-400')} value={form.name} onChange={set('name')} autoComplete="name" maxLength={120} {...aria('name')} />
                    </Field>
                    <Field id="quote-company" label={t('Company')} error={errors.company}>
                      <input id="quote-company" className="input" value={form.company} onChange={set('company')} autoComplete="organization" maxLength={150} />
                    </Field>
                    <Field id="quote-email" label={t('Email')} required error={errors.email}>
                      <input id="quote-email" type="email" className={cn('input', errors.email && 'border-red-400')} value={form.email} onChange={set('email')} autoComplete="email" maxLength={150} {...aria('email')} />
                    </Field>
                    <Field id="quote-phone" label={t('Phone / WhatsApp')} required error={errors.phone}>
                      <input id="quote-phone" type="tel" className={cn('input', errors.phone && 'border-red-400')} value={form.phone} onChange={set('phone')} autoComplete="tel" maxLength={30} {...aria('phone')} />
                    </Field>
                    <Field id="quote-message" label={t('Additional details')} className="sm:col-span-2" error={errors.message}>
                      <textarea id="quote-message" rows={4} className="input" value={form.message} onChange={set('message')} maxLength={5000} />
                    </Field>
                  </div>
                  <div className="hidden" aria-hidden="true">
                    <label htmlFor="quote-website">Website</label>
                    <input id="quote-website" tabIndex={-1} autoComplete="off" value={form.website} onChange={set('website')} />
                  </div>
                </fieldset>

                {status.state === 'error' && <p role="alert" className="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">{status.message}</p>}
                <div className="flex justify-end">
                  <button type="submit" className="btn-primary px-8 py-3" disabled={status.state === 'loading'}>
                    {status.state === 'loading' && <Spinner className="h-4 w-4 text-white" label="Sending" />}
                    {t('Request Quotation')} <ArrowRight className="h-4 w-4" aria-hidden="true" />
                  </button>
                </div>
              </form>
            )}
          </div>
          <aside className="space-y-4">
            {[
              [Clock, 'Fast response', 'Our sales team replies within one working day.'],
              [PackageCheck, 'Custom solutions', 'Any size, colour, neck finish or printing on request.'],
              [Truck, 'Nationwide delivery', 'On-time delivery across Bangladesh and export on request.'],
            ].map(([IconComponent, title, text]) => (
              <div key={title} className="card flex gap-4 p-5">
                <span className="icon-circle h-12 w-12"><IconComponent className="h-6 w-6" aria-hidden="true" /></span>
                <div>
                  <h2 className="font-semibold text-navy-900">{t(title)}</h2>
                  <p className="mt-1 text-sm text-muted">{t(text)}</p>
                </div>
              </div>
            ))}
          </aside>
        </div>
      </section>
    </>
  )
}
