import { useEffect, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { CalendarDays, Mail, Newspaper, Search } from 'lucide-react'
import { useNewsCategories, useNews, useRecentNews } from '../lib/queries'
import { NewsCard } from '../components/ui/Cards'
import { CardGridSkeleton, EmptyState, ErrorState } from '../components/ui/States'
import Pagination from '../components/ui/Pagination'
import NewsletterForm from '../components/layout/NewsletterForm'
import { cn, formatDate } from '../lib/utils'

export function NewsSidebar({ activeCategory, onCategory }) {
  const { data: categories = [] } = useNewsCategories()
  const { data: recent } = useRecentNews()
  const total = categories.reduce((sum, c) => sum + (c.posts_count || 0), 0)

  return (
    <aside className="space-y-6">
      <div className="card p-5">
        <h2 className="text-lg font-semibold text-navy-900">Categories</h2>
        <ul className="mt-3 space-y-1 text-sm">
          {[{ slug: '', name: 'All News', posts_count: total }, ...categories].map((c) => (
            <li key={c.slug || 'all'}>
              {onCategory ? (
                <button
                  type="button"
                  onClick={() => onCategory(c.slug)}
                  className={cn('flex w-full items-center justify-between rounded-md px-3 py-2 text-left', activeCategory === c.slug ? 'bg-brand-50 font-medium text-brand-600' : 'text-slate-600 hover:bg-slate-50')}
                >
                  {c.name} <span>({c.posts_count})</span>
                </button>
              ) : (
                <Link to={c.slug ? `/news?category=${c.slug}` : '/news'} className="flex items-center justify-between rounded-md px-3 py-2 text-slate-600 hover:bg-slate-50">
                  {c.name} <span>({c.posts_count})</span>
                </Link>
              )}
            </li>
          ))}
        </ul>
      </div>

      {recent?.data?.length > 0 && (
        <div className="card p-5">
          <h2 className="text-lg font-semibold text-navy-900">Recent News</h2>
          <ul className="mt-4 space-y-4">
            {recent.data.map((post) => (
              <li key={post.id} className="flex gap-3">
                {post.featured_image_url && <img src={post.featured_image_url} alt="" loading="lazy" className="h-16 w-20 shrink-0 rounded-md object-cover" />}
                <div>
                  <Link to={`/news/${post.slug}`} className="text-sm leading-snug font-medium text-navy-900 hover:text-brand-600">{post.title}</Link>
                  <p className="mt-1 flex items-center gap-1 text-xs text-muted">
                    <CalendarDays className="h-3.5 w-3.5" aria-hidden="true" /> {formatDate(post.published_at)}
                  </p>
                </div>
              </li>
            ))}
          </ul>
        </div>
      )}

      <div className="card p-5">
        <div className="flex items-start gap-3">
          <Mail className="h-9 w-9 shrink-0 text-brand-600" aria-hidden="true" />
          <div>
            <h2 className="text-lg font-semibold text-navy-900">Newsletter</h2>
            <p className="mt-1 text-sm text-muted">Subscribe to get the latest news and updates.</p>
          </div>
        </div>
        <div className="mt-4">
          <NewsletterForm compact idPrefix="sidebar-newsletter" />
        </div>
      </div>
    </aside>
  )
}

export default function NewsModule() {
  const [params, setParams] = useSearchParams()
  const category = params.get('category') || ''
  const q = params.get('q') || ''
  const page = Number(params.get('page')) || 1
  const [term, setTerm] = useState(q)
  const { data: categories = [] } = useNewsCategories()
  const { data, isLoading, isError, refetch, isFetching } = useNews({ category: category || undefined, q: q || undefined, page, per_page: 6 })

  useEffect(() => setTerm(q), [q])

  function update(next) {
    const merged = new URLSearchParams(params)
    Object.entries(next).forEach(([k, v]) => (v ? merged.set(k, String(v)) : merged.delete(k)))
    setParams(merged)
  }

  const posts = data?.data || []

  return (
    <section className="py-12">
      <div className="container-x">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div className="flex flex-wrap gap-2" role="group" aria-label="Filter news by category">
            {[{ slug: '', name: 'All News' }, ...categories.filter((c) => c.posts_count > 0)].map((c) => (
              <button
                key={c.slug || 'all'}
                type="button"
                aria-pressed={category === c.slug}
                onClick={() => update({ category: c.slug, page: '' })}
                className={cn('rounded-md px-5 py-2.5 text-sm font-medium transition-colors', category === c.slug ? 'bg-brand-600 text-white' : 'bg-brand-50 text-navy-900 hover:bg-brand-100')}
              >
                {c.name}
              </button>
            ))}
          </div>
          <form
            className="flex w-full lg:w-80"
            role="search"
            onSubmit={(e) => {
              e.preventDefault()
              update({ q: term.trim(), page: '' })
            }}
          >
            <label htmlFor="news-search" className="sr-only">Search news</label>
            <input id="news-search" type="search" value={term} onChange={(e) => setTerm(e.target.value)} placeholder="Search news…" className="input rounded-r-none" />
            <button type="submit" className="btn-primary rounded-l-none px-4" aria-label="Search news">
              <Search className="h-4 w-4" aria-hidden="true" />
            </button>
          </form>
        </div>

        <div className="mt-8 grid gap-8 lg:grid-cols-[1fr_300px]">
          <div className={cn(isFetching && !isLoading && 'opacity-60')} aria-live="polite" aria-busy={isFetching}>
            {isLoading ? (
              <CardGridSkeleton className="grid gap-6 sm:grid-cols-2 xl:grid-cols-3" />
            ) : isError ? (
              <ErrorState onRetry={refetch} />
            ) : posts.length === 0 ? (
              <EmptyState icon={Newspaper} title="No news found" message={q ? `No results for "${q}".` : 'There are no published news posts in this category yet.'} />
            ) : (
              <>
                <div className="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                  {posts.map((post) => (
                    <NewsCard key={post.id} post={post} />
                  ))}
                </div>
                <Pagination meta={data.meta} onChange={(p) => update({ page: p > 1 ? p : '' })} className="mt-10" />
              </>
            )}
          </div>
          <NewsSidebar activeCategory={category} onCategory={(slug) => update({ category: slug, page: '' })} />
        </div>
      </div>
    </section>
  )
}
