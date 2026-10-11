import { useQuery } from '@tanstack/react-query'
import { useSearchParams } from 'react-router-dom'
import { Mail, MapPin, Phone, Store, User } from 'lucide-react'
import { CardGridSkeleton, EmptyState, ErrorState } from '../components/ui/States'
import { api } from '../lib/api'
import { districtName } from '../lib/districts'
import { useLang } from '../lib/i18n'
import { safeHref, telHref } from '../lib/utils'

const TYPE_LABELS = { dealer: 'Dealer', distributor: 'Distributor', agent: 'Agent' }

/** Dealer finder rendered inside the "Dealers" CMS page (template: dealers). */
export default function DealersModule({ section }) {
  const { lang, t } = useLang()
  const [params, setParams] = useSearchParams()
  const district = params.get('district') || ''
  const { data, isLoading, isError, refetch } = useQuery({
    queryKey: ['dealers', district],
    queryFn: () => api.get('/dealers', { params: { district: district || undefined } }).then((r) => r.data),
    placeholderData: (prev) => prev,
  })

  const choose = (value) => {
    const next = new URLSearchParams(params)
    if (value) next.set('district', value)
    else next.delete('district')
    setParams(next, { replace: true })
  }

  return (
    <section className="py-14">
      <div className="container-x">
        <div className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
          <div>
            {section?.eyebrow && <p className="eyebrow">{section.eyebrow}</p>}
            <h2 className="heading-lg mt-2">{section?.title || t('Find a Dealer Near You')}</h2>
          </div>
          <div className="w-full sm:w-72">
            <label htmlFor="dealer-district" className="label">{t('District')}</label>
            <select id="dealer-district" className="input" value={district} onChange={(e) => choose(e.target.value)}>
              <option value="">{t('All districts')}</option>
              {(data?.districts || []).map((d) => <option key={d} value={d}>{districtName(d, lang)}</option>)}
            </select>
          </div>
        </div>

        {isLoading ? (
          <CardGridSkeleton count={3} />
        ) : isError ? (
          <ErrorState onRetry={refetch} />
        ) : !data.data.length ? (
          <EmptyState icon={Store} title={t('No dealers listed yet')} message={t('Please contact us and we will connect you with the nearest dealer.')} />
        ) : (
          <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {data.data.map((d) => (
              <li key={d.id} className="card flex flex-col gap-3 p-5 text-sm">
                <div className="flex items-start justify-between gap-3">
                  <h3 className="text-base font-semibold text-navy-900">{d.name}</h3>
                  <span className="shrink-0 rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-medium text-brand-700">{t(TYPE_LABELS[d.type] || d.type)}</span>
                </div>
                <p className="flex items-center gap-2 font-medium text-slate-700"><MapPin className="h-4 w-4 shrink-0 text-brand-600" aria-hidden="true" />{districtName(d.district, lang)}</p>
                {d.address && <p className="whitespace-pre-line text-muted">{d.address}</p>}
                {d.contact_person && <p className="flex items-center gap-2 text-slate-700"><User className="h-4 w-4 shrink-0 text-brand-600" aria-hidden="true" />{d.contact_person}</p>}
                {d.phone && <a href={telHref(d.phone)} className="flex items-center gap-2 text-navy-900 hover:text-brand-600"><Phone className="h-4 w-4 shrink-0 text-brand-600" aria-hidden="true" />{d.phone}</a>}
                {d.email && <a href={`mailto:${d.email}`} className="flex items-center gap-2 break-all text-navy-900 hover:text-brand-600"><Mail className="h-4 w-4 shrink-0 text-brand-600" aria-hidden="true" />{d.email}</a>}
                {safeHref(d.map_url) && <a href={safeHref(d.map_url)} target="_blank" rel="noopener noreferrer" className="mt-auto pt-1 text-sm font-semibold text-brand-600">{t('Open in Google Maps')} →</a>}
              </li>
            ))}
          </ul>
        )}
      </div>
    </section>
  )
}
