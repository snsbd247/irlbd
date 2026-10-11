import { useEffect, useState } from 'react'
import { Link, useParams, useSearchParams } from 'react-router-dom'
import { ArrowRight, CalendarDays, CircleCheck, FileDown, FileSearch, MessageSquareQuote, Tag } from 'lucide-react'
import { JsonLd } from '../components/layout/Integrations'
import Seo from '../components/Seo'
import PageHero from '../components/layout/PageHero'
import SectionRenderer from '../components/sections/SectionRenderer'
import { RichText } from '../components/sections/ContentSections'
import { ProductCard } from '../components/ui/Cards'
import { EmptyState, ErrorState, PageLoader, Spinner } from '../components/ui/States'
import { api, errorMessage } from '../lib/api'
import { Icon } from '../lib/icons'
import { useHomePage, useNavigation, useNewsPost, usePage, useProduct, useSearch, useService } from '../lib/queries'
import { cn, formatDate, stripHtml } from '../lib/utils'
import ProductsModule from './ProductsModule'
import { CompareToggle } from './ComparePage'
import NewsModule, { NewsSidebar } from './NewsModule'
import ContactModule from './ContactModule'
import { GalleryModule } from './GalleryPages'
import { CareersModule } from './CareerPages'

const isNotFound = (error) => error?.response?.status === 404

/** Breadcrumb trail derived from the header menu (e.g. Home › About › Our Mission). */
function useCrumbs(path, title) {
  const { data: nav } = useNavigation()
  for (const item of nav?.header || []) {
    if ((item.children || []).some((c) => c.href === path)) {
      return [{ label: item.label, href: item.href }, { label: title }]
    }
  }
  return [{ label: title }]
}

export function HomePage() {
  const { data: page, isLoading, isError, refetch } = useHomePage()
  if (isLoading) return <PageLoader />
  if (isError) return <div className="container-x py-20"><ErrorState onRetry={refetch} /></div>
  return (
    <>
      <Seo title={page.meta_title} description={page.meta_description} keywords={page.meta_keywords} image={page.og_image_url} />
      <SectionRenderer sections={page.sections} />
    </>
  )
}

const MODULES = {
  products: (section) => <ProductsModule key="products-module" section={section} />,
  news: () => <NewsModule key="news-module" />,
  contact: () => <ContactModule key="contact-module" />,
  gallery: () => <GalleryModule key="gallery-module" />,
  careers: (section) => <CareersModule key="careers-module" section={section} />,
}

export function DynamicPage() {
  const { slug } = useParams()
  const { data: page, isLoading, isError, error, refetch } = usePage(slug)
  const crumbs = useCrumbs(`/${slug}`, page?.title || '')

  if (isLoading) return <PageLoader />
  if (isNotFound(error)) return <NotFound />
  if (isError) return <div className="container-x py-20"><ErrorState onRetry={refetch} /></div>

  return (
    <>
      <Seo title={page.meta_title || page.title} description={page.meta_description || page.hero_subtitle} keywords={page.meta_keywords} image={page.og_image_url || page.hero_image_url} />
      <PageHero title={page.hero_title || page.title} subtitle={page.hero_subtitle} image={page.hero_image_url} crumbs={crumbs}>
        {page.content && page.template === 'contact' && <p className="mt-4 max-w-xl text-sm text-white/85">{stripHtml(page.content)}</p>}
      </PageHero>
      {page.content && page.template !== 'contact' && (
        <section className="pt-12">
          <div className={cn('container-x', page.template === 'sections' && 'max-w-4xl')}>
            <RichText html={page.content} />
          </div>
        </section>
      )}
      <SectionRenderer sections={page.sections} module={MODULES[page.template]} />
    </>
  )
}

export function ProductDetail() {
  const { slug } = useParams()
  const { data, isLoading, isError, error, refetch } = useProduct(slug)
  const [active, setActive] = useState(0)

  useEffect(() => setActive(0), [slug])

  if (isLoading) return <PageLoader />
  if (isNotFound(error)) return <NotFound />
  if (isError) return <div className="container-x py-20"><ErrorState onRetry={refetch} /></div>

  const product = data.data
  const gallery = product.images?.length ? product.images : product.image_url ? [{ id: 0, url: product.image_url, alt: product.name }] : []
  const quoteUrl = `/request-quote?product=${encodeURIComponent(product.slug)}`
  const crumbs = [{ label: 'Our Products', href: '/products' }, ...(product.category ? [{ label: product.category.name, href: `/products?category=${product.category.slug}` }] : []), { label: product.name }]

  return (
    <>
      <Seo title={product.meta_title || product.name} description={product.meta_description || product.short_description} image={product.image_url} type="product" />
      <JsonLd
        data={{
          '@context': 'https://schema.org',
          '@type': 'Product',
          name: product.name,
          sku: product.sku || undefined,
          description: product.short_description || stripHtml(product.description).slice(0, 300) || undefined,
          image: gallery.map((g) => g.url),
          category: product.category?.name,
          brand: { '@type': 'Brand', name: 'IR Limited' },
          additionalProperty: (product.specifications || []).map((sp) => ({ '@type': 'PropertyValue', name: sp.label, value: sp.value })),
        }}
      />
      <PageHero title={product.name} subtitle={product.category?.name} image={product.category?.image_url} crumbs={crumbs} />
      <section className="py-14">
        <div className="container-x grid gap-10 lg:grid-cols-2">
          <div>
            <div className="aspect-[4/3] overflow-hidden rounded-2xl bg-brand-50">
              {gallery[active] && <img src={gallery[active].url} alt={gallery[active].alt || product.name} className="h-full w-full object-contain" />}
            </div>
            {gallery.length > 1 && (
              <ul className="mt-4 grid grid-cols-4 gap-3 sm:grid-cols-5">
                {gallery.map((img, i) => (
                  <li key={img.id ?? i}>
                    <button type="button" onClick={() => setActive(i)} aria-label={`Show image ${i + 1}`} aria-current={i === active} className={cn('block aspect-square w-full overflow-hidden rounded-lg border-2 bg-brand-50', i === active ? 'border-brand-600' : 'border-transparent hover:border-brand-200')}>
                      <img src={img.url} alt="" loading="lazy" className="h-full w-full object-cover" />
                    </button>
                  </li>
                ))}
              </ul>
            )}
          </div>
          <div>
            {product.category && (
              <Link to={`/products?category=${product.category.slug}`} className="inline-flex items-center gap-1.5 text-xs font-semibold tracking-wider text-brand-600 uppercase">
                <Tag className="h-3.5 w-3.5" aria-hidden="true" /> {product.category.name}
              </Link>
            )}
            <h2 className="heading-lg mt-2">{product.name}</h2>
            {product.sku && <p className="mt-1 text-sm text-muted">Product code: {product.sku}</p>}
            {product.short_description && <p className="mt-4 text-[15px] text-slate-700">{product.short_description}</p>}
            {product.industries?.length > 0 && (
              <div className="mt-4">
                <p className="mb-2 text-xs font-semibold tracking-wide text-muted uppercase">Suitable for</p>
                <ul className="flex flex-wrap gap-2">
                  {product.industries.map((i) => (
                    <li key={i.id}>
                      <Link to={`/products?industry=${i.slug}`} className="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-700 hover:bg-brand-100">
                        <Icon name={i.icon} className="h-3.5 w-3.5" /> {i.name}
                      </Link>
                    </li>
                  ))}
                </ul>
              </div>
            )}

            {product.specifications?.length > 0 && (
              <div className="mt-6 overflow-hidden rounded-xl border border-slate-200">
                <table className="w-full text-sm">
                  <caption className="bg-brand-50 px-4 py-3 text-left font-semibold text-navy-900">Specifications</caption>
                  <tbody>
                    {product.specifications.map((spec) => (
                      <tr key={spec.id} className="border-t border-slate-100">
                        <th scope="row" className="w-2/5 bg-slate-50/60 px-4 py-2.5 text-left font-medium text-navy-900">{spec.label}</th>
                        <td className="px-4 py-2.5 text-slate-700">{spec.value}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
            <div className="mt-6 flex flex-wrap gap-3">
              <Link to={quoteUrl} className="btn-primary px-6 py-3">
                <MessageSquareQuote className="h-4 w-4" aria-hidden="true" /> Request a Quote
              </Link>
              <CompareToggle slug={product.slug} />
              {product.datasheet_url && (
                <a href={product.datasheet_url} target="_blank" rel="noopener noreferrer" className="btn-outline px-6 py-3">
                  <FileDown className="h-4 w-4" aria-hidden="true" /> Download Datasheet
                </a>
              )}
              <Link to="/products" className="btn-outline px-6 py-3">Back to Products</Link>
            </div>
          </div>
        </div>
        {product.variants?.length > 0 && (
          <div className="container-x mt-12">
            <h2 className="mb-4 text-xl font-semibold text-navy-900">Available Sizes & Variants</h2>
            <div className="overflow-x-auto rounded-xl border border-slate-200">
              <table className="w-full min-w-[560px] text-sm">
                <thead className="bg-brand-50/60 text-left text-xs font-semibold tracking-wide text-navy-900 uppercase">
                  <tr>
                    {['Variant', 'Capacity', 'Weight', 'Neck size', 'Colour', 'MOQ'].map((h) => <th key={h} scope="col" className="px-4 py-3">{h}</th>)}
                  </tr>
                </thead>
                <tbody>
                  {product.variants.map((v) => (
                    <tr key={v.id} className="border-t border-slate-100">
                      <th scope="row" className="px-4 py-3 text-left font-semibold text-navy-900">{v.name}</th>
                      {['capacity', 'weight', 'neck_size', 'color', 'moq'].map((k) => <td key={k} className="px-4 py-3 text-slate-700">{v[k] || '—'}</td>)}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}
        {product.description && (
          <div className="container-x mt-12">
            <div className="rounded-2xl border border-slate-100 p-6 sm:p-8">
              <h2 className="mb-4 text-xl font-semibold text-navy-900">Product Details</h2>
              <RichText html={product.description} />
            </div>
          </div>
        )}
      </section>
      {data.related?.length > 0 && (
        <section className="bg-brand-50/50 py-14">
          <div className="container-x">
            <h2 className="heading-lg">Related Products</h2>
            <div className="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
              {data.related.map((p) => (
                <ProductCard key={p.id} product={p} />
              ))}
            </div>
          </div>
        </section>
      )}
    </>
  )
}

export function NewsDetail() {
  const { slug } = useParams()
  const { data, isLoading, isError, error, refetch } = useNewsPost(slug)

  if (isLoading) return <PageLoader />
  if (isNotFound(error)) return <NotFound />
  if (isError) return <div className="container-x py-20"><ErrorState onRetry={refetch} /></div>

  const post = data.data
  const shareUrl = encodeURIComponent(window.location.href)

  return (
    <>
      <Seo title={post.meta_title || post.title} description={post.meta_description || post.excerpt || stripHtml(post.content).slice(0, 160)} image={post.featured_image_url} type="article" />
      <JsonLd
        data={{
          '@context': 'https://schema.org',
          '@type': 'NewsArticle',
          headline: post.title,
          datePublished: post.published_at,
          dateModified: post.updated_at,
          image: post.featured_image_url ? [post.featured_image_url] : undefined,
          description: post.excerpt || undefined,
          publisher: { '@type': 'Organization', name: 'IR Limited' },
        }}
      />
      <PageHero title={post.title} crumbs={[{ label: 'News', href: '/news' }, { label: post.title }]}>
        <p className="mt-4 flex flex-wrap items-center gap-4 text-sm text-white/85">
          <span className="flex items-center gap-1.5"><CalendarDays className="h-4 w-4" aria-hidden="true" /> <time dateTime={post.published_at}>{formatDate(post.published_at, { day: 'numeric', month: 'long', year: 'numeric' })}</time></span>
          {post.category && <span className="rounded bg-white/15 px-2 py-0.5 text-xs font-semibold uppercase">{post.category.name}</span>}
        </p>
      </PageHero>
      <section className="py-12">
        <div className="container-x grid gap-10 lg:grid-cols-[1fr_300px]">
          <article>
            {post.featured_image_url && <img src={post.featured_image_url} alt="" className="mb-8 aspect-[16/9] w-full rounded-2xl object-cover" />}
            {post.excerpt && <p className="mb-6 text-lg leading-relaxed text-slate-700">{post.excerpt}</p>}
            <RichText html={post.content} />
            <div className="mt-10 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-6 text-sm">
              <span className="font-semibold text-navy-900">Share:</span>
              <a className="btn-outline py-1.5" href={`https://www.facebook.com/sharer/sharer.php?u=${shareUrl}`} target="_blank" rel="noopener noreferrer">Facebook</a>
              <a className="btn-outline py-1.5" href={`https://www.linkedin.com/sharing/share-offsite/?url=${shareUrl}`} target="_blank" rel="noopener noreferrer">LinkedIn</a>
              <a className="btn-outline py-1.5" href={`https://x.com/intent/post?url=${shareUrl}&text=${encodeURIComponent(post.title)}`} target="_blank" rel="noopener noreferrer">X</a>
            </div>
            <Link to="/news" className="mt-8 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600">← Back to all news</Link>
          </article>
          <NewsSidebar />
        </div>
      </section>
    </>
  )
}

export function ServiceDetail() {
  const { slug } = useParams()
  const { data: service, isLoading, isError, error, refetch } = useService(slug)

  if (isLoading) return <PageLoader />
  if (isNotFound(error)) return <NotFound />
  if (isError) return <div className="container-x py-20"><ErrorState onRetry={refetch} /></div>

  return (
    <>
      <Seo title={service.title} description={service.short_description} image={service.image_url} />
      <PageHero title={service.title} subtitle={service.short_description} image={service.image_url} crumbs={[{ label: 'Services', href: '/#services' }, { label: service.title }]} />
      <section className="py-14">
        <div className="container-x grid gap-10 lg:grid-cols-[1fr_320px]">
          <div>
            <span className="icon-circle h-16 w-16 [&>svg]:h-8 [&>svg]:w-8"><Icon name={service.icon} /></span>
            <RichText html={service.description || `<p>${service.short_description || ''}</p>`} className="mt-6" />
          </div>
          <aside className="card h-fit p-6">
            <h2 className="text-lg font-semibold text-navy-900">Need this service?</h2>
            <p className="mt-2 text-sm text-muted">Tell us about your packaging requirements and our team will get back to you within 24 hours.</p>
            <Link to={`/contact?subject=${encodeURIComponent('Custom Packaging')}`} className="btn-primary mt-5 w-full">
              Contact Us <ArrowRight className="h-4 w-4" aria-hidden="true" />
            </Link>
          </aside>
        </div>
      </section>
    </>
  )
}

export function SearchPage() {
  const [params, setParams] = useSearchParams()
  const q = params.get('q') || ''
  const [term, setTerm] = useState(q)
  const { data, isFetching, isError, refetch } = useSearch(q)

  useEffect(() => setTerm(q), [q])

  const results = data?.results || []
  const label = { product: 'Product', news: 'News', page: 'Page' }

  return (
    <>
      <Seo title={q ? `Search: ${q}` : 'Search'} noindex />
      <PageHero title="Search" subtitle={q ? `Results for "${q}"` : 'Find products, news and pages'} crumbs={[{ label: 'Search' }]} />
      <section className="py-12">
        <div className="container-x max-w-4xl">
          <form role="search" className="flex" onSubmit={(e) => { e.preventDefault(); if (term.trim().length >= 2) setParams({ q: term.trim() }) }}>
            <label htmlFor="search-page" className="sr-only">Search</label>
            <input id="search-page" type="search" className="input rounded-r-none" value={term} onChange={(e) => setTerm(e.target.value)} placeholder="Search…" minLength={2} />
            <button type="submit" className="btn-primary rounded-l-none">Search</button>
          </form>
          <div className="mt-8" aria-live="polite">
            {q.length < 2 ? (
              <p className="text-sm text-muted">Enter at least 2 characters to search.</p>
            ) : isFetching ? (
              <div className="flex justify-center py-10"><Spinner /></div>
            ) : isError ? (
              <ErrorState onRetry={refetch} />
            ) : results.length === 0 ? (
              <EmptyState icon={FileSearch} title="No results found" message="Try a different keyword, or browse our products." />
            ) : (
              <ul className="space-y-4">
                {results.map((r) => (
                  <li key={r.type + r.url}>
                    <Link to={r.url} className="card flex gap-4 p-4 hover:shadow-lg">
                      {r.image_url && <img src={r.image_url} alt="" loading="lazy" className="h-20 w-24 shrink-0 rounded-md object-cover" />}
                      <div>
                        <span className="text-xs font-semibold tracking-wide text-brand-600 uppercase">{label[r.type]}</span>
                        <p className="font-semibold text-navy-900">{r.title}</p>
                        {r.excerpt && <p className="mt-1 line-clamp-2 text-sm text-muted">{r.excerpt}</p>}
                      </div>
                    </Link>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </div>
      </section>
    </>
  )
}

export function UnsubscribePage() {
  const [params] = useSearchParams()
  const token = params.get('token') || ''
  const [state, setState] = useState({ status: 'idle', message: '' })

  async function unsubscribe() {
    setState({ status: 'loading', message: '' })
    try {
      const { data } = await api.post('/newsletter/unsubscribe', { token })
      setState({ status: 'done', message: data.message })
    } catch (error) {
      setState({ status: 'error', message: errorMessage(error) })
    }
  }

  return (
    <>
      <Seo title="Unsubscribe" noindex />
      <section className="container-x flex min-h-[50vh] max-w-xl flex-col items-center justify-center py-16 text-center">
        <h1 className="heading-lg">Newsletter subscription</h1>
        {state.status === 'done' ? (
          <p className="mt-4 flex items-center gap-2 text-emerald-700"><CircleCheck className="h-5 w-5" aria-hidden="true" /> {state.message}</p>
        ) : (
          <>
            <p className="mt-3 text-muted">Click below to stop receiving our newsletter.</p>
            <button type="button" className="btn-primary mt-6" onClick={unsubscribe} disabled={!token || state.status === 'loading'}>
              {state.status === 'loading' && <Spinner className="h-4 w-4 text-white" />} Unsubscribe
            </button>
            {!token && <p className="mt-3 text-sm text-red-600">This unsubscribe link is invalid.</p>}
            {state.status === 'error' && <p className="mt-3 text-sm text-red-600">{state.message}</p>}
          </>
        )}
      </section>
    </>
  )
}

export function NotFound() {
  return (
    <>
      <Seo title="Page not found" noindex />
      <section className="container-x flex min-h-[60vh] flex-col items-center justify-center py-16 text-center">
        <p className="text-7xl font-bold text-brand-600">404</p>
        <h1 className="heading-lg mt-4">Page not found</h1>
        <p className="mt-3 max-w-md text-muted">The page you are looking for doesn’t exist or has been moved.</p>
        <div className="mt-8 flex gap-3">
          <Link to="/" className="btn-primary">Go to homepage</Link>
          <Link to="/products" className="btn-outline">View products</Link>
        </div>
      </section>
    </>
  )
}
