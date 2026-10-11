import { Link, useLocation, useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ArrowRight, GitCompareArrows, MessageSquareQuote, X } from 'lucide-react'
import Seo from '../components/Seo'
import PageHero from '../components/layout/PageHero'
import { EmptyState, ErrorState, PageLoader } from '../components/ui/States'
import { api } from '../lib/api'
import { COMPARE_MAX, useCompare } from '../lib/compare'
import { cn } from '../lib/utils'

/** "Add to compare" toggle used on product cards and product pages. */
export function CompareToggle({ slug, className, compact = false }) {
  const { has, toggle, full } = useCompare()
  const selected = has(slug)
  const disabled = !selected && full
  return (
    <button
      type="button"
      onClick={(e) => {
        e.preventDefault()
        toggle(slug)
      }}
      disabled={disabled}
      aria-pressed={selected}
      title={disabled ? `You can compare up to ${COMPARE_MAX} products` : undefined}
      className={cn(
        'inline-flex items-center gap-1.5 rounded-md border text-sm font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-50',
        compact ? 'px-2.5 py-1.5 text-xs' : 'px-4 py-2.5',
        selected ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-300 bg-white text-slate-700 hover:border-brand-600 hover:text-brand-600',
        className,
      )}
    >
      <GitCompareArrows className="h-4 w-4" aria-hidden="true" /> {selected ? 'Added to compare' : 'Compare'}
    </button>
  )
}

/** Floating bar shown while products are selected for comparison. */
export function CompareBar() {
  const { list, clear } = useCompare()
  const navigate = useNavigate()
  const { pathname } = useLocation()
  // Not needed on the comparison page itself (it would cover the table).
  if (!list.length || pathname === '/compare') return null
  return (
    <div className="fixed inset-x-0 bottom-4 z-30 flex justify-center px-4" role="region" aria-label="Product comparison">
      <div className="flex items-center gap-3 rounded-full bg-navy-900 py-2 pr-2 pl-5 text-sm text-white shadow-2xl">
        <GitCompareArrows className="h-4 w-4 text-brand-500" aria-hidden="true" />
        <span>{list.length} product{list.length > 1 ? 's' : ''} selected</span>
        <button type="button" onClick={clear} className="text-white/70 hover:text-white">Clear</button>
        <button type="button" onClick={() => navigate('/compare')} disabled={list.length < 2} className="btn-primary rounded-full px-4 py-1.5 disabled:opacity-50" title={list.length < 2 ? 'Select at least 2 products' : undefined}>
          Compare <ArrowRight className="h-4 w-4" aria-hidden="true" />
        </button>
      </div>
    </div>
  )
}

const ROW_LABELS = [
  ['category', 'Category', (p) => p.category?.name],
  ['sku', 'Product code', (p) => p.sku],
  ['industries', 'Industries', (p) => (p.industries || []).map((i) => i.name).join(', ')],
  ['variants', 'Available sizes', (p) => (p.variants || []).map((v) => v.name).join(', ')],
]

export default function ComparePage() {
  const { list, remove } = useCompare()
  const slugs = list.join(',')
  const { data: products, isLoading, isError, refetch } = useQuery({
    queryKey: ['compare', slugs],
    queryFn: () => api.get('/products/compare', { params: { slugs } }).then((r) => r.data),
    enabled: list.length > 0,
  })

  const specLabels = [...new Set((products || []).flatMap((p) => (p.specifications || []).map((s) => s.label)))]

  return (
    <>
      <Seo title="Compare Products" noindex />
      <PageHero title="Compare Products" subtitle="Side-by-side specifications of the products you selected." crumbs={[{ label: 'Our Products', href: '/products' }, { label: 'Compare' }]} />
      <section className="py-12">
        <div className="container-x">
          {list.length === 0 ? (
            <EmptyState icon={GitCompareArrows} title="No products selected" message={`Use the “Compare” button on up to ${COMPARE_MAX} products, then come back here.`}>
              <Link to="/products" className="btn-primary mt-3">Browse products</Link>
            </EmptyState>
          ) : isLoading ? (
            <PageLoader />
          ) : isError ? (
            <ErrorState onRetry={refetch} />
          ) : (
            <div className="overflow-x-auto rounded-xl border border-slate-200">
              <table className="w-full min-w-[640px] table-fixed text-sm">
                <caption className="sr-only">Product comparison</caption>
                <thead>
                  <tr className="bg-brand-50/60">
                    <th scope="col" className="w-44 p-4 text-left text-xs font-semibold text-muted uppercase">Product</th>
                    {products.map((p) => (
                      <th key={p.id} scope="col" className="p-4 text-left align-top">
                        <div className="relative">
                          <button type="button" onClick={() => remove(p.slug)} className="absolute -top-1 right-0 rounded-full p-1 text-slate-400 hover:bg-white hover:text-red-600" aria-label={`Remove ${p.name} from comparison`}>
                            <X className="h-4 w-4" aria-hidden="true" />
                          </button>
                          {p.image_url && <img src={p.image_url} alt="" className="mb-3 aspect-[4/3] w-full rounded-lg bg-white object-contain" />}
                          <Link to={`/products/${p.slug}`} className="font-semibold text-navy-900 hover:text-brand-600">{p.name}</Link>
                        </div>
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {ROW_LABELS.map(([key, label, value]) => (
                    <tr key={key} className="border-t border-slate-100">
                      <th scope="row" className="bg-slate-50/60 p-4 text-left font-medium text-navy-900">{label}</th>
                      {products.map((p) => <td key={p.id} className="p-4 align-top text-slate-700">{value(p) || '—'}</td>)}
                    </tr>
                  ))}
                  {specLabels.map((label) => (
                    <tr key={label} className="border-t border-slate-100">
                      <th scope="row" className="bg-slate-50/60 p-4 text-left font-medium text-navy-900">{label}</th>
                      {products.map((p) => <td key={p.id} className="p-4 align-top text-slate-700">{p.specifications?.find((s) => s.label === label)?.value || '—'}</td>)}
                    </tr>
                  ))}
                  <tr className="border-t border-slate-100">
                    <th scope="row" className="bg-slate-50/60 p-4" />
                    {products.map((p) => (
                      <td key={p.id} className="p-4">
                        <Link to={`/request-quote?product=${encodeURIComponent(p.slug)}`} className="btn-primary w-full py-2">
                          <MessageSquareQuote className="h-4 w-4" aria-hidden="true" /> Get a Quote
                        </Link>
                      </td>
                    ))}
                  </tr>
                </tbody>
              </table>
            </div>
          )}
        </div>
      </section>
    </>
  )
}
