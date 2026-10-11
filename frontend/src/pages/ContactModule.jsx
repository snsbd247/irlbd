import { useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { ArrowRight, CircleCheck, Headphones, Mail, MapPin, Phone } from 'lucide-react'
import { api, errorMessage, fieldErrors } from '../lib/api'
import { useContactInfo, useSettings } from '../lib/queries'
import { cn, telHref } from '../lib/utils'
import { Spinner } from '../components/ui/States'
import { translate, useT } from '../lib/i18n'

function InfoCard({ icon: IconComponent, title, children }) {
  return (
    <div className="card flex gap-4 p-5">
      <span className="icon-circle h-14 w-14">
        <IconComponent className="h-6 w-6" aria-hidden="true" />
      </span>
      <div className="min-w-0 text-sm">
        <h2 className="mb-1.5 text-base font-semibold text-navy-900">{title}</h2>
        {children}
      </div>
    </div>
  )
}

const EMPTY = { name: '', email: '', phone: '', subject: '', message: '', website: '' }

function validate(form) {
  const e = {}
  if (form.name.trim().length < 2) e.name = translate('Please enter your name.')
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) e.email = translate('Please enter a valid email address.')
  if (form.phone && !/^[0-9+\-\s()]{6,30}$/.test(form.phone.trim())) e.phone = translate('Please enter a valid phone number.')
  if (!form.subject) e.subject = translate('Please select a subject.')
  if (form.message.trim().length < 10) e.message = translate('Your message must be at least 10 characters.')
  return e
}

function Field({ id, label, required, error, children }) {
  return (
    <div>
      <label htmlFor={id} className="label">
        {label} {required && <span className="text-red-500" aria-hidden="true">*</span>}
      </label>
      {children}
      {error && (
        <p id={`${id}-error`} className="mt-1 text-xs text-red-600">{error}</p>
      )}
    </div>
  )
}

export function ContactForm({ subjects }) {
  const t = useT()
  const [params] = useSearchParams()
  const product = params.get('product')
  const [form, setForm] = useState(() => ({
    ...EMPTY,
    subject: params.get('subject') && subjects?.includes(params.get('subject')) ? params.get('subject') : '',
    message: product ? `I would like a quotation for: ${product}\n\nQuantity: \nDetails: ` : '',
  }))
  const [errors, setErrors] = useState({})
  const [status, setStatus] = useState({ state: 'idle', message: '' })

  const set = (key) => (e) => setForm((f) => ({ ...f, [key]: e.target.value }))
  const describedBy = (key) => (errors[key] ? `contact-${key}-error` : undefined)

  async function submit(e) {
    e.preventDefault()
    const v = validate(form)
    setErrors(v)
    if (Object.keys(v).length) {
      document.getElementById(`contact-${Object.keys(v)[0]}`)?.focus()
      return
    }
    setStatus({ state: 'loading', message: '' })
    try {
      const { data } = await api.post('/contact', form)
      setStatus({ state: 'success', message: data.message })
      setForm(EMPTY)
    } catch (error) {
      setErrors(fieldErrors(error))
      setStatus({ state: 'error', message: errorMessage(error, 'Your message could not be sent. Please try again or email us directly.') })
    }
  }

  if (status.state === 'success') {
    return (
      <div role="status" className="flex flex-col items-center gap-3 rounded-xl bg-emerald-50 px-6 py-12 text-center">
        <CircleCheck className="h-12 w-12 text-emerald-600" aria-hidden="true" />
        <p className="text-lg font-semibold text-navy-900">{t('Message sent')}</p>
        <p className="max-w-sm text-sm text-muted">{status.message}</p>
        <button type="button" className="btn-outline mt-2" onClick={() => setStatus({ state: 'idle', message: '' })}>{t('Send another message')}</button>
      </div>
    )
  }

  return (
    <form onSubmit={submit} noValidate className="space-y-4 rounded-xl bg-white p-5 sm:p-6">
      <div className="grid gap-4 sm:grid-cols-2">
        <Field id="contact-name" label={t('Your Name')} required error={errors.name}>
          <input id="contact-name" className={cn('input', errors.name && 'border-red-400')} value={form.name} onChange={set('name')} placeholder="Enter your name" autoComplete="name" required aria-invalid={!!errors.name} aria-describedby={describedBy('name')} maxLength={120} />
        </Field>
        <Field id="contact-email" label={t('Your Email')} required error={errors.email}>
          <input id="contact-email" type="email" className={cn('input', errors.email && 'border-red-400')} value={form.email} onChange={set('email')} placeholder="Enter your email" autoComplete="email" required aria-invalid={!!errors.email} aria-describedby={describedBy('email')} maxLength={150} />
        </Field>
        <Field id="contact-phone" label={t('Phone Number')} error={errors.phone}>
          <input id="contact-phone" type="tel" className={cn('input', errors.phone && 'border-red-400')} value={form.phone} onChange={set('phone')} placeholder="Enter your phone number" autoComplete="tel" aria-invalid={!!errors.phone} aria-describedby={describedBy('phone')} maxLength={30} />
        </Field>
        <Field id="contact-subject" label={t('Subject')} required error={errors.subject}>
          <select id="contact-subject" className={cn('input', errors.subject && 'border-red-400', !form.subject && 'text-slate-400')} value={form.subject} onChange={set('subject')} required aria-invalid={!!errors.subject} aria-describedby={describedBy('subject')}>
            <option value="">{t('Select a subject')}</option>
            {(subjects || []).map((s) => (
              <option key={s} value={s} className="text-ink">{s}</option>
            ))}
          </select>
        </Field>
      </div>
      <Field id="contact-message" label={t('Message')} required error={errors.message}>
        <textarea id="contact-message" rows={5} className={cn('input', errors.message && 'border-red-400')} value={form.message} onChange={set('message')} placeholder="Write your message here…" required aria-invalid={!!errors.message} aria-describedby={describedBy('message')} maxLength={5000} />
      </Field>
      {/* Honeypot: hidden from people, filled by bots. */}
      <div className="hidden" aria-hidden="true">
        <label htmlFor="contact-website">Website</label>
        <input id="contact-website" tabIndex={-1} autoComplete="off" value={form.website} onChange={set('website')} />
      </div>
      {status.state === 'error' && <p role="alert" className="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">{status.message}</p>}
      <div className="flex justify-end">
        <button type="submit" className="btn-primary px-7 py-3" disabled={status.state === 'loading'}>
          {status.state === 'loading' ? <Spinner className="h-4 w-4 text-white" label="Sending" /> : null}
          {t('Send Message')} <ArrowRight className="h-4 w-4" aria-hidden="true" />
        </button>
      </div>
    </form>
  )
}

export default function ContactModule() {
  const t = useT()
  const { data: s = {} } = useSettings()
  const { data: info } = useContactInfo()

  return (
    <>
      <section className="py-10">
        <div className="container-x grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
          <InfoCard icon={Phone} title={t('Call Us')}>
            {[s.contact_phone, s.contact_phone_secondary].filter(Boolean).map((p) => (
              <a key={p} href={telHref(p)} className="block text-navy-900 hover:text-brand-600">{p}</a>
            ))}
            {s.office_hours && <p className="mt-1 text-xs text-muted">{s.office_hours}</p>}
          </InfoCard>
          <InfoCard icon={Mail} title={t('Email Us')}>
            {[s.contact_email, s.contact_email_secondary].filter(Boolean).map((m) => (
              <a key={m} href={`mailto:${m}`} className="block break-all text-navy-900 hover:text-brand-600">{m}</a>
            ))}
            <p className="mt-1 text-xs text-muted">{t('We reply within 24 hours')}</p>
          </InfoCard>
          <InfoCard icon={MapPin} title={t('Our Location')}>
            <p className="whitespace-pre-line text-muted">{s.contact_address}</p>
          </InfoCard>
          <InfoCard icon={Headphones} title={t('Support')}>
            <p className="text-muted">{t('For product inquiries, quotations or other support, our team is ready to help you.')}</p>
          </InfoCard>
        </div>
      </section>

      <section className="pb-14">
        <div className="container-x grid gap-6 lg:grid-cols-2">
          <div className="rounded-2xl bg-brand-50/70 p-5 sm:p-6">
            <p className="eyebrow">{t('Send us a message')}</p>
            <h2 className="heading-lg mt-2">{t('Get in Touch')}</h2>
            <p className="mt-2 mb-5 text-sm text-muted">{t('Fill out the form below and our team will get back to you shortly.')}</p>
            <ContactForm key={info ? 'ready' : 'loading'} subjects={info?.subjects} />
          </div>
          <div className="flex flex-col rounded-2xl bg-brand-50/70 p-5 sm:p-6">
            <p className="eyebrow">{t('Find us here')}</p>
            <h2 className="heading-lg mt-2">{t('Our Location')}</h2>
            <p className="mt-2 mb-5 text-sm text-muted">{t('Visit our office or factory.')}</p>
            {s.map_embed_url ? (
              <iframe
                src={s.map_embed_url}
                title={`Map showing ${s.site_name || 'our'} location`}
                className="min-h-80 w-full flex-1 rounded-xl border-0"
                loading="lazy"
                referrerPolicy="no-referrer-when-downgrade"
                allowFullScreen
              />
            ) : (
              <div className="flex min-h-80 flex-1 items-center justify-center rounded-xl bg-white text-sm text-muted">Map not configured.</div>
            )}
            {s.map_link_url && (
              <a href={s.map_link_url} target="_blank" rel="noopener noreferrer" className="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600">
                {t('Open in Google Maps')} <ArrowRight className="h-4 w-4" aria-hidden="true" />
              </a>
            )}
          </div>
        </div>
      </section>

      {info?.offices?.length > 0 && (
        <section className="pb-6">
          <div className="container-x">
            <p className="eyebrow">{t('Our Offices')}</p>
            <h2 className="heading-lg mt-2">{t('Our Offices')}</h2>
            <p className="mt-2 text-sm text-muted">{t('We look forward to hearing from you and exploring how we can work together.')}</p>
            <div className="mt-6 grid gap-5 md:grid-cols-2">
              {info.offices.map((office) => (
                <article key={office.id} className="card flex flex-col gap-4 p-4 sm:flex-row">
                  {office.image_url && <img src={office.image_url} alt="" loading="lazy" className="h-40 w-full rounded-lg object-cover sm:h-auto sm:w-40" />}
                  <div className="text-sm">
                    <h3 className="text-base font-semibold text-navy-900">{office.name}</h3>
                    {office.address && (
                      <p className="mt-2 flex gap-2 whitespace-pre-line text-muted">
                        <MapPin className="mt-0.5 h-4 w-4 shrink-0 text-brand-600" aria-hidden="true" /> {office.address}
                      </p>
                    )}
                    {office.phone && (
                      <a href={telHref(office.phone)} className="mt-2 flex items-center gap-2 text-navy-900 hover:text-brand-600">
                        <Phone className="h-4 w-4 text-brand-600" aria-hidden="true" /> {office.phone}
                      </a>
                    )}
                    {office.email && (
                      <a href={`mailto:${office.email}`} className="mt-1 flex items-center gap-2 text-navy-900 hover:text-brand-600">
                        <Mail className="h-4 w-4 text-brand-600" aria-hidden="true" /> {office.email}
                      </a>
                    )}
                  </div>
                </article>
              ))}
            </div>
          </div>
        </section>
      )}
    </>
  )
}
