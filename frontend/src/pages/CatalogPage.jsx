import { Fragment } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { Printer } from 'lucide-react'
import Seo from '../components/Seo'
import { ErrorState, PageLoader } from '../components/ui/States'
import { api } from '../lib/api'
import { useSettings } from '../lib/queries'
import { useT } from '../lib/i18n'

/**
 * Printable product catalog built from the live product data. "Download PDF" opens the
 * browser's print dialog, where visitors choose "Save as PDF" (works for Bengali too,
 * no server-side PDF library needed).
 */
export default function CatalogPage() {
  const t = useT()
  const { data: s = {} } = useSettings()
  const { data: products, isLoading, isError, refetch } = useQuery({ queryKey: ['catalog'], queryFn: () => api.get('/products/catalog').then((r) => r.data) })

  if (isLoading) return <PageLoader />
  if (isError) return <div className="container-x py-20"><ErrorState onRetry={refetch} /></div>

  const groups = []
  products.forEach((p) => {
    const name = p.category?.name || t('Other products')
    const last = groups[groups.length - 1]
    if (last && last.name === name) last.items.push(p)
    else groups.push({ name, items: [p] })
  })

  return (
    <>
      <Seo title={t('Product Catalog')} description={`${t('Product Catalog')} – ${s.site_name || 'IR Limited'}`} />
      <div className="container-x max-w-5xl py-10 print:max-w-none print:px-0 print:py-0">
        <div className="mb-8 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-6">
          <div className="flex items-center gap-4">
            {s.logo_header_url && <img src={s.logo_header_url} alt={s.site_name || ''} className="h-14 w-auto" />}
            <div>
              <h1 className="text-2xl font-bold text-navy-900">{t('Product Catalog')}</h1>
              <p className="text-sm text-muted">{s.site_tagline}</p>
            </div>
          </div>
          <div className="flex gap-3 print:hidden">
            <Link to="/products" className="btn-outline">{t('Our Products')}</Link>
            <button type="button" className="btn-primary" onClick={() => window.print()}>
              <Printer className="h-4 w-4" aria-hidden="true" /> {t('Download PDF / Print')}
            </button>
          </div>
          <p className="w-full text-xs text-muted print:hidden">{t('Tip: choose "Save as PDF" as the printer to download the catalog.')}</p>
        </div>

        {groups.map((g) => (
          <section key={g.name} className="mb-10">
            <h2 className="mb-4 rounded-md bg-navy-900 px-4 py-2 text-lg font-semibold text-white">{g.name}</h2>
            <div className="space-y-6">
              {g.items.map((p) => (
                <article key={p.id} className="grid break-inside-avoid gap-5 rounded-xl border border-slate-200 p-4 sm:grid-cols-[180px_1fr] print:grid-cols-[150px_1fr]">
                  {p.image_url ? (
                    <img src={p.image_url} alt={p.name} className="aspect-square w-full rounded-lg bg-brand-50 object-contain" />
                  ) : (
                    <div className="aspect-square w-full rounded-lg bg-brand-50" aria-hidden="true" />
                  )}
                  <div className="min-w-0">
                    <h3 className="text-lg font-semibold text-navy-900">{p.name}</h3>
                    {p.sku && <p className="text-xs text-muted">{t('Product code')}: {p.sku}</p>}
                    {p.short_description && <p className="mt-2 text-sm text-slate-700">{p.short_description}</p>}
                    {p.specifications?.length > 0 && (
                      <dl className="mt-3 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm">
                        {p.specifications.map((sp) => (
                          <Fragment key={sp.id}>
                            <dt className="font-medium text-navy-900">{sp.label}</dt>
                            <dd className="text-slate-700">{sp.value}</dd>
                          </Fragment>
                        ))}
                      </dl>
                    )}
                    {p.variants?.length > 0 && (
                      <table className="mt-3 w-full text-left text-xs">
                        <thead className="bg-slate-100 text-slate-600">
                          <tr>{['Variant', 'Capacity', 'Weight', 'Neck size', 'Colour', 'MOQ'].map((h) => <th key={h} scope="col" className="px-2 py-1.5 font-semibold">{t(h)}</th>)}</tr>
                        </thead>
                        <tbody>
                          {p.variants.map((v) => (
                            <tr key={v.id} className="border-t border-slate-100">
                              {[v.name, v.capacity, v.weight, v.neck_size, v.color, v.moq].map((c, i) => <td key={i} className="px-2 py-1.5">{c || '—'}</td>)}
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    )}
                  </div>
                </article>
              ))}
            </div>
          </section>
        ))}

        <footer className="mt-10 border-t border-slate-200 pt-4 text-xs text-muted">
          <p className="font-semibold text-navy-900">{s.site_name}</p>
          {s.contact_address && <p className="whitespace-pre-line">{s.contact_address}</p>}
          <p>{[s.contact_phone, s.contact_email, window.location.host].filter(Boolean).join(' · ')}</p>
          <p className="mt-1">{t('Catalog generated on')} {new Date().toLocaleDateString()}</p>
        </footer>
      </div>
    </>
  )
}
