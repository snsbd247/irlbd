import { useEffect, useRef } from 'react'
import { Link } from 'react-router-dom'
import { ArrowRight, CalendarDays, X } from 'lucide-react'
import { Icon } from '../../lib/icons'
import { dateParts, formatDate } from '../../lib/utils'
import { CompareToggle } from '../../pages/ComparePage'

export function ProductCard({ product }) {
  return (
    <article className="card group flex flex-col overflow-hidden transition-shadow hover:shadow-lg">
      <Link to={`/products/${product.slug}`} className="block aspect-[4/3] overflow-hidden bg-brand-50" tabIndex={-1} aria-hidden="true">
        {product.image_url && (
          <img src={product.image_url} alt="" loading="lazy" className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
        )}
      </Link>
      <div className="flex flex-1 flex-col p-5">
        {product.category && <p className="text-xs font-semibold tracking-wide text-brand-600 uppercase">{product.category.name}</p>}
        <h3 className="mt-1 font-semibold text-navy-900">
          <Link to={`/products/${product.slug}`} className="hover:text-brand-600">{product.name}</Link>
        </h3>
        {product.short_description && <p className="mt-2 line-clamp-2 text-sm text-muted">{product.short_description}</p>}
        <div className="mt-auto flex items-center justify-between gap-2 pt-4">
          <Link to={`/products/${product.slug}`} className="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 hover:gap-2.5" aria-label={`View details of ${product.name}`}>
            View Details <ArrowRight className="h-4 w-4 transition-all" aria-hidden="true" />
          </Link>
          <CompareToggle slug={product.slug} compact />
        </div>
      </div>
    </article>
  )
}

export function MiniProductCard({ product }) {
  return (
    <Link to={`/products/${product.slug}`} className="card group flex flex-col overflow-hidden hover:shadow-lg">
      <div className="aspect-[4/3] overflow-hidden bg-brand-50">
        {product.image_url && <img src={product.image_url} alt="" loading="lazy" className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />}
      </div>
      <div className="flex items-center justify-between gap-2 px-4 py-3">
        <span className="text-sm font-medium text-navy-900">{product.name}</span>
        <ArrowRight className="h-4 w-4 shrink-0 text-brand-600" aria-hidden="true" />
      </div>
    </Link>
  )
}

export function NewsCard({ post }) {
  const d = dateParts(post.published_at)
  return (
    <article className="card group flex flex-col overflow-hidden transition-shadow hover:shadow-lg">
      <Link to={`/news/${post.slug}`} className="relative block aspect-[16/10] overflow-hidden bg-brand-50" tabIndex={-1} aria-hidden="true">
        {post.featured_image_url && <img src={post.featured_image_url} alt="" loading="lazy" className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />}
        {d && (
          <span className="absolute bottom-3 left-3 rounded-md bg-white px-3 py-1.5 text-center shadow">
            <span className="block text-xl leading-none font-bold text-navy-900">{d.day}</span>
            <span className="block text-[10px] font-medium text-muted">{d.month} {d.year}</span>
          </span>
        )}
      </Link>
      <div className="flex flex-1 flex-col p-5">
        {post.category && <p className="text-xs font-semibold tracking-wide text-brand-600 uppercase">{post.category.name}</p>}
        <h3 className="mt-1.5 text-lg leading-snug font-semibold text-navy-900">
          <Link to={`/news/${post.slug}`} className="hover:text-brand-600">{post.title}</Link>
        </h3>
        {post.excerpt && <p className="mt-2 line-clamp-3 text-sm text-muted">{post.excerpt}</p>}
        <Link to={`/news/${post.slug}`} className="mt-auto inline-flex items-center gap-1.5 pt-4 text-sm font-semibold text-brand-600" aria-label={`Read more: ${post.title}`}>
          Read More <ArrowRight className="h-4 w-4" aria-hidden="true" />
        </Link>
      </div>
    </article>
  )
}

export function NewsRowCard({ post }) {
  return (
    <article className="card flex gap-4 p-3">
      <Link to={`/news/${post.slug}`} className="block w-28 shrink-0 overflow-hidden rounded-lg bg-brand-50 sm:w-36" tabIndex={-1} aria-hidden="true">
        {post.featured_image_url && <img src={post.featured_image_url} alt="" loading="lazy" className="h-full min-h-24 w-full object-cover" />}
      </Link>
      <div className="min-w-0 py-1">
        <p className="flex items-center gap-1 text-xs text-muted">
          <CalendarDays className="h-3.5 w-3.5" aria-hidden="true" /> {formatDate(post.published_at)}
        </p>
        <h3 className="mt-1 text-sm leading-snug font-semibold text-navy-900">
          <Link to={`/news/${post.slug}`} className="hover:text-brand-600">{post.title}</Link>
        </h3>
        {post.excerpt && <p className="mt-1 line-clamp-2 text-xs text-muted">{post.excerpt}</p>}
        <Link to={`/news/${post.slug}`} className="mt-1.5 inline-flex items-center gap-1 text-xs font-semibold text-brand-600">
          Read More <ArrowRight className="h-3.5 w-3.5" aria-hidden="true" />
        </Link>
      </div>
    </article>
  )
}

export function IconBadge({ name, size = 'md', className = '' }) {
  const sizes = { sm: 'h-10 w-10 [&>svg]:h-5 [&>svg]:w-5', md: 'h-14 w-14 [&>svg]:h-7 [&>svg]:w-7', lg: 'h-16 w-16 [&>svg]:h-8 [&>svg]:w-8' }
  return (
    <span className={`icon-circle ${sizes[size]} ${className}`}>
      <Icon name={name} />
    </span>
  )
}

export function VideoModal({ url, onClose }) {
  const ref = useRef(null)
  useEffect(() => {
    ref.current?.focus()
    const onKey = (e) => e.key === 'Escape' && onClose()
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [onClose])

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" role="dialog" aria-modal="true" aria-label="Video">
      <button type="button" className="absolute inset-0" onClick={onClose} aria-label="Close video" />
      <div className="relative w-full max-w-4xl">
        <button ref={ref} type="button" onClick={onClose} className="absolute -top-11 right-0 rounded-full bg-white/10 p-2 text-white hover:bg-white/20" aria-label="Close video">
          <X className="h-5 w-5" aria-hidden="true" />
        </button>
        <div className="aspect-video overflow-hidden rounded-lg bg-black">
          <iframe src={url} title="Video" className="h-full w-full" allow="autoplay; encrypted-media; picture-in-picture" allowFullScreen referrerPolicy="strict-origin-when-cross-origin" />
        </div>
      </div>
    </div>
  )
}
