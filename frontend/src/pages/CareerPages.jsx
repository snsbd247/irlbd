import { useRef, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ArrowLeft, ArrowRight, Briefcase, CalendarClock, CircleCheck, FileUp, MapPin, Users, Wallet } from 'lucide-react'
import Seo from '../components/Seo'
import PageHero from '../components/layout/PageHero'
import { RichText } from '../components/sections/ContentSections'
import { EmptyState, ErrorState, PageLoader, Skeleton, Spinner } from '../components/ui/States'
import { api, errorMessage, fieldErrors } from '../lib/api'
import { useSettings } from '../lib/queries'
import { cn, formatDate } from '../lib/utils'
import { NotFound } from './PublicPages'
import { useT } from '../lib/i18n'

export const JOB_TYPES = { full_time: 'Full-time', part_time: 'Part-time', contract: 'Contract', internship: 'Internship' }
const CV_MAX_BYTES = 5 * 1024 * 1024

function JobMeta({ job, className }) {
  const bits = [
    job.department && [Briefcase, job.department],
    job.location && [MapPin, job.location],
    [Users, `${JOB_TYPES[job.employment_type] || job.employment_type}${job.vacancies ? ` · ${job.vacancies} vacanc${job.vacancies > 1 ? 'ies' : 'y'}` : ''}`],
    job.deadline && [CalendarClock, `Apply by ${formatDate(job.deadline)}`],
    job.salary && [Wallet, job.salary],
  ].filter(Boolean)
  return (
    <ul className={cn('flex flex-wrap gap-x-5 gap-y-1.5 text-sm', className)}>
      {bits.map(([IconComponent, text]) => (
        <li key={text} className="flex items-center gap-1.5">
          <IconComponent className="h-4 w-4 shrink-0" aria-hidden="true" /> {text}
        </li>
      ))}
    </ul>
  )
}

/** Job list rendered inside the "Careers" CMS page (template: careers). */
export function CareersModule({ section }) {
  const t = useT()
  const { data: s = {} } = useSettings()
  const { data: jobs, isLoading, isError, refetch } = useQuery({ queryKey: ['jobs'], queryFn: () => api.get('/jobs').then((r) => r.data) })

  return (
    <section className="py-14">
      <div className="container-x max-w-5xl">
        {section?.title && (
          <div className="mb-8">
            {section.eyebrow && <p className="eyebrow">{section.eyebrow}</p>}
            <h2 className="heading-lg mt-2">{section.title}</h2>
          </div>
        )}
        {isLoading ? (
          <div className="space-y-4">{[1, 2].map((i) => <Skeleton key={i} className="h-28 w-full" />)}</div>
        ) : isError ? (
          <ErrorState onRetry={refetch} />
        ) : !jobs.length ? (
          <EmptyState icon={Briefcase} title={t('No open positions right now')} message={`We are always happy to hear from talented people.${s.contact_email ? ` Send your CV to ${s.contact_email}.` : ''}`} />
        ) : (
          <ul className="space-y-4">
            {jobs.map((job) => (
              <li key={job.id}>
                <Link to={`/careers/${job.slug}`} className="card group flex flex-col gap-4 p-6 transition-shadow hover:shadow-lg sm:flex-row sm:items-center sm:justify-between">
                  <div>
                    <h3 className="text-lg font-semibold text-navy-900 group-hover:text-brand-600">{job.title}</h3>
                    <JobMeta job={job} className="mt-2 text-muted" />
                    {job.summary && <p className="mt-2 text-sm text-slate-600">{job.summary}</p>}
                  </div>
                  <span className="btn-primary shrink-0">{t('View & Apply')} <ArrowRight className="h-4 w-4" aria-hidden="true" /></span>
                </Link>
              </li>
            ))}
          </ul>
        )}
      </div>
    </section>
  )
}

function ApplyForm({ job }) {
  const t = useT()
  const fileRef = useRef(null)
  const [form, setForm] = useState({ name: '', email: '', phone: '', cover_letter: '', consent: false, website: '' })
  const [cv, setCv] = useState(null)
  const [errors, setErrors] = useState({})
  const [status, setStatus] = useState({ state: 'idle', message: '' })
  const set = (k) => (e) => setForm((f) => ({ ...f, [k]: e.target.type === 'checkbox' ? e.target.checked : e.target.value }))

  function validate() {
    const e = {}
    if (form.name.trim().length < 2) e.name = 'Please enter your name.'
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) e.email = 'Please enter a valid email address.'
    if (!/^[0-9+\-\s()]{6,30}$/.test(form.phone.trim())) e.phone = 'Please enter a valid phone number.'
    if (!cv) e.cv = 'Please attach your CV.'
    else if (!/\.(pdf|docx?)$/i.test(cv.name)) e.cv = 'Your CV must be a PDF or Word (.doc / .docx) document.'
    else if (cv.size > CV_MAX_BYTES) e.cv = 'Your CV may not be larger than 5 MB.'
    if (!form.consent) e.consent = 'Please agree to the processing of your application data.'
    return e
  }

  async function submit(e) {
    e.preventDefault()
    const v = validate()
    setErrors(v)
    if (Object.keys(v).length) {
      document.getElementById(`apply-${Object.keys(v)[0]}`)?.focus()
      return
    }
    setStatus({ state: 'loading', message: '' })
    const body = new FormData()
    Object.entries(form).forEach(([k, val]) => body.append(k, k === 'consent' ? (val ? '1' : '0') : val))
    body.append('cv', cv)
    try {
      const { data } = await api.post(`/jobs/${encodeURIComponent(job.slug)}/apply`, body, { headers: { 'Content-Type': 'multipart/form-data' } })
      setStatus({ state: 'success', message: data.message })
    } catch (error) {
      setErrors(fieldErrors(error))
      setStatus({ state: 'error', message: errorMessage(error, 'Your application could not be sent. Please try again.') })
    }
  }

  if (status.state === 'success') {
    return (
      <div role="status" className="flex flex-col items-center gap-3 rounded-xl bg-emerald-50 px-6 py-12 text-center">
        <CircleCheck className="h-12 w-12 text-emerald-600" aria-hidden="true" />
        <p className="text-lg font-semibold text-navy-900">{t('Application sent')}</p>
        <p className="max-w-sm text-sm text-muted">{status.message}</p>
      </div>
    )
  }

  const err = (k) => errors[k] && <p id={`apply-${k}-error`} className="mt-1 text-xs text-red-600">{errors[k]}</p>
  const aria = (k) => ({ 'aria-invalid': !!errors[k], 'aria-describedby': errors[k] ? `apply-${k}-error` : undefined })

  return (
    <form onSubmit={submit} noValidate className="space-y-4">
      <div>
        <label htmlFor="apply-name" className="label">{t('Full name')} <span className="text-red-500">*</span></label>
        <input id="apply-name" className={cn('input', errors.name && 'border-red-400')} value={form.name} onChange={set('name')} autoComplete="name" maxLength={120} {...aria('name')} />
        {err('name')}
      </div>
      <div className="grid gap-4 sm:grid-cols-2">
        <div>
          <label htmlFor="apply-email" className="label">{t('Email')} <span className="text-red-500">*</span></label>
          <input id="apply-email" type="email" className={cn('input', errors.email && 'border-red-400')} value={form.email} onChange={set('email')} autoComplete="email" maxLength={150} {...aria('email')} />
          {err('email')}
        </div>
        <div>
          <label htmlFor="apply-phone" className="label">{t('Phone')} <span className="text-red-500">*</span></label>
          <input id="apply-phone" type="tel" className={cn('input', errors.phone && 'border-red-400')} value={form.phone} onChange={set('phone')} autoComplete="tel" maxLength={30} {...aria('phone')} />
          {err('phone')}
        </div>
      </div>
      <div>
        <span className="label">{t('CV / Resume')} <span className="text-red-500">*</span></span>
        <button
          id="apply-cv"
          type="button"
          onClick={() => fileRef.current?.click()}
          className={cn('flex w-full items-center gap-3 rounded-md border-2 border-dashed px-4 py-4 text-left text-sm hover:border-brand-600', errors.cv ? 'border-red-400' : 'border-slate-300')}
          {...aria('cv')}
        >
          <FileUp className="h-6 w-6 shrink-0 text-brand-600" aria-hidden="true" />
          <span>
            <span className="block font-medium text-navy-900">{cv ? cv.name : t('Choose file')}</span>
            <span className="block text-xs text-muted">{cv ? `${(cv.size / 1024).toFixed(0)} KB` : 'PDF or Word (.doc / .docx), max 5 MB'}</span>
          </span>
        </button>
        <input ref={fileRef} type="file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" className="hidden" onChange={(e) => setCv(e.target.files[0] || null)} />
        {err('cv')}
      </div>
      <div>
        <label htmlFor="apply-cover_letter" className="label">{t('Cover letter (optional)')}</label>
        <textarea id="apply-cover_letter" rows={5} className="input" value={form.cover_letter} onChange={set('cover_letter')} maxLength={5000} />
      </div>
      <div className="hidden" aria-hidden="true">
        <label htmlFor="apply-website">Website</label>
        <input id="apply-website" tabIndex={-1} autoComplete="off" value={form.website} onChange={set('website')} />
      </div>
      <label className="flex items-start gap-2 text-sm text-slate-600">
        <input id="apply-consent" type="checkbox" className="mt-0.5 h-4 w-4 accent-brand-600" checked={form.consent} onChange={set('consent')} {...aria('consent')} />
        <span>I agree that IR Limited may store and process my application and CV for recruitment purposes. <span className="text-red-500">*</span></span>
      </label>
      {err('consent')}
      {status.state === 'error' && <p role="alert" className="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">{status.message}</p>}
      <button type="submit" className="btn-primary w-full py-3" disabled={status.state === 'loading'}>
        {status.state === 'loading' && <Spinner className="h-4 w-4 text-white" label="Sending" />} {t('Submit Application')}
      </button>
    </form>
  )
}

export function JobDetailPage() {
  const t = useT()
  const { slug } = useParams()
  const { data: job, isLoading, isError, error, refetch } = useQuery({ queryKey: ['job', slug], queryFn: () => api.get(`/jobs/${encodeURIComponent(slug)}`).then((r) => r.data) })

  if (isLoading) return <PageLoader />
  if (error?.response?.status === 404) return <NotFound />
  if (isError) return <div className="container-x py-20"><ErrorState onRetry={refetch} /></div>

  return (
    <>
      <Seo title={job.meta_title || `${job.title} – Careers`} description={job.meta_description || job.summary} />
      <PageHero title={job.title} crumbs={[{ label: t('Careers'), href: '/careers' }, { label: job.title }]}>
        <JobMeta job={job} className="mt-4 text-white/85" />
      </PageHero>
      <section className="py-12">
        <div className="container-x grid gap-10 lg:grid-cols-[1fr_380px]">
          <article>
            {job.summary && <p className="mb-6 text-lg text-slate-700">{job.summary}</p>}
            <RichText html={job.description} />
            <Link to="/careers" className="mt-10 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600">
              <ArrowLeft className="h-4 w-4" aria-hidden="true" /> {t('All openings')}
            </Link>
          </article>
          <aside className="card h-fit p-6 lg:sticky lg:top-24">
            <h2 className="mb-4 text-lg font-semibold text-navy-900">{t('Apply for this position')}</h2>
            {job.is_open ? (
              <ApplyForm job={job} />
            ) : (
              <p className="rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800">{t('Applications for this position are closed.')}</p>
            )}
          </aside>
        </div>
      </section>
    </>
  )
}
