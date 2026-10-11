import { useEffect, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { PackageSearch, Search, X } from 'lucide-react'
import { useProductCategories, useProducts } from '../lib/queries'
import { ProductCard } from '../components/ui/Cards'
import { CardGridSkeleton, EmptyState, ErrorState } from '../components/ui/States'
import Pagination from '../components/ui/Pagination'
import SectionHeading from '../components/ui/SectionHeading'
import BrochureButton from '../components/ui/BrochureButton'
import { cn } from '../lib/utils'

/** Searchable, filterable, paginated product catalog. State lives in the URL (?category=&q=&page=). */
export default function ProductsModule({ section }) {
  const [params, setParams] = useSearchParams()
  const category = params.get('category') || ''
  const q = params.get('q') || ''
  const page = Number(params.get('page')) || 1
  const [term, setTerm] = useState(q)

  const { data: categories = [] } = useProductCategories()
  const { data, isLoading, isError, refetch, isFetching } = useProducts({ category: category || undefined, q: q || undefined, page, per_page: 9 })

  useEffect(() => setTerm(q), [q])

  // Debounced search.
  useEffect(() => {
    if (term === q) return
    const t = setTimeout(() => update({ q: term.trim(), page: '' }), 400)
    return () => clearTimeout(t)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [term])

  function update(next) {
    const merged = new URLSearchParams(params)
    Object.entries(next).forEach(([k, v]) => (v ? merged.set(k, String(v)) : merged.delete(k)))
    setParams(merged, { replace: false })
  }

  function goToPage(p) {
    update({ page: p > 1 ? p : '' })
    document.getElementById('catalog')?.scrollIntoView({ behavior: 'smooth' })
  }

  const products = data?.data || []
  const activeCategory = categories.find((c) => c.slug === category)

  return (
    <section id="catalog" className="scroll-mt-24 py-14">
      <div className="container-x">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
          <SectionHeading eyebrow={section?.eyebrow} title={activeCategory ? activeCategory.name : section?.title || 'All Products'} subtitle={activeCategory?.short_description || section?.subtitle} />
          <BrochureButton className="btn-outline shrink-0" />
        </div>

        <div className="mt-8 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div className="flex flex-wrap gap-2" role="group" aria-label="Filter by category">
            {[{ slug: '', name: 'All Products' }, ...categories].map((c) => (
              <button
                key={c.slug || 'all'}
                type="button"
                onClick={() => update({ category: c.slug, page: '' })}
                aria-pressed={category === c.slug}
                className={cn(
                  'rounded-md px-4 py-2 text-sm font-medium transition-colors',
                  category === c.slug ? 'bg-brand-600 text-white' : 'bg-brand-50 text-navy-900 hover:bg-brand-100',
                )}
              >
                {c.name}
                {c.products_count !== undefined && <span className="ml-1.5 opacity-70">({c.products_count})</span>}
              </button>
            ))}
          </div>
          <div className="relative w-full lg:w-80">
            <label htmlFor="product-search" className="sr-only">Search products</label>
            <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
            <input id="product-search" type="search" value={term} onChange={(e) => setTerm(e.target.value)} placeholder="Search products…" className="input pr-9 pl-9" />
            {term && (
              <button type="button" onClick={() => setTerm('')} className="absolute top-1/2 right-2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600" aria-label="Clear search">
                <X className="h-4 w-4" aria-hidden="true" />
              </button>
            )}
          </div>
        </div>

        <div className={cn('mt-8 transition-opacity', isFetching && !isLoading && 'opacity-60')} aria-live="polite" aria-busy={isFetching}>
          {isLoading ? (
            <CardGridSkeleton />
          ) : isError ? (
            <ErrorState onRetry={refetch} />
          ) : products.length === 0 ? (
            <EmptyState icon={PackageSearch} title="No products found" message="Try another category or search term.">
              {(q || category) && (
                <button type="button" className="btn-outline mt-2" onClick={() => setParams({})}>Clear filters</button>
              )}
            </EmptyState>
          ) : (
            <>
              <p className="mb-4 text-sm text-muted">
                Showing {data.meta.from}–{data.meta.to} of {data.meta.total} products
              </p>
              <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                {products.map((product) => (
                  <ProductCard key={product.id} product={product} />
                ))}
              </div>
              <Pagination meta={data.meta} onChange={goToPage} className="mt-10" />
            </>
          )}
        </div>
      </div>
    </section>
  )
}
