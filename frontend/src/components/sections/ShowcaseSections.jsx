import { useCallback, useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { ArrowRight, ChevronLeft, ChevronRight, Images, Play, Quote, Star, X } from 'lucide-react'
import SectionHeading from '../ui/SectionHeading'
import SmartLink from '../ui/SmartLink'
import { VideoModal } from '../ui/Cards'
import { cn, youtubeEmbed } from '../../lib/utils'

/** Accessible full-screen image viewer with keyboard (←/→/Esc) and swipe-free button navigation. */
export function Lightbox({ images, index, onClose, onIndex }) {
  const closeRef = useRef(null)
  const go = useCallback((dir) => onIndex((index + dir + images.length) % images.length), [index, images.length, onIndex])

  useEffect(() => {
    const previous = document.activeElement
    closeRef.current?.focus()
    const onKey = (e) => {
      if (e.key === 'Escape') onClose()
      if (e.key === 'ArrowRight') go(1)
      if (e.key === 'ArrowLeft') go(-1)
    }
    window.addEventListener('keydown', onKey)
    document.body.style.overflow = 'hidden'
    return () => {
      window.removeEventListener('keydown', onKey)
      document.body.style.overflow = ''
      previous?.focus?.()
    }
  }, [go, onClose])

  const image = images[index]
  return (
    <div className="fixed inset-0 z-50 flex flex-col bg-black/95" role="dialog" aria-modal="true" aria-label="Image viewer">
      <div className="flex items-center justify-between px-4 py-3 text-sm text-white/80">
        <span aria-live="polite">{index + 1} / {images.length}</span>
        <button ref={closeRef} type="button" onClick={onClose} className="rounded-full p-2 hover:bg-white/10" aria-label="Close viewer">
          <X className="h-6 w-6" aria-hidden="true" />
        </button>
      </div>
      <figure className="relative flex min-h-0 flex-1 items-center justify-center px-4 pb-4 sm:px-16">
        <img src={image.url} alt={image.caption || image.alt || ''} className="max-h-full max-w-full object-contain" />
        {images.length > 1 && (
          <>
            <button type="button" onClick={() => go(-1)} className="absolute top-1/2 left-2 -translate-y-1/2 rounded-full bg-white/10 p-3 text-white hover:bg-white/20" aria-label="Previous image">
              <ChevronLeft className="h-6 w-6" aria-hidden="true" />
            </button>
            <button type="button" onClick={() => go(1)} className="absolute top-1/2 right-2 -translate-y-1/2 rounded-full bg-white/10 p-3 text-white hover:bg-white/20" aria-label="Next image">
              <ChevronRight className="h-6 w-6" aria-hidden="true" />
            </button>
          </>
        )}
        {image.caption && <figcaption className="absolute inset-x-0 bottom-6 text-center text-sm text-white">{image.caption}</figcaption>}
      </figure>
    </div>
  )
}

export function AlbumCard({ album }) {
  return (
    <Link to={`/gallery/${album.slug}`} className="card group block overflow-hidden">
      <div className="relative aspect-[4/3] overflow-hidden bg-brand-50">
        {album.cover_image_url && <img src={album.cover_image_url} alt="" loading="lazy" className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />}
        {album.images_count !== undefined && (
          <span className="absolute right-3 bottom-3 inline-flex items-center gap-1 rounded-full bg-black/60 px-2.5 py-1 text-xs font-medium text-white">
            <Images className="h-3.5 w-3.5" aria-hidden="true" /> {album.images_count}
          </span>
        )}
      </div>
      <div className="p-5">
        <h3 className="font-semibold text-navy-900 group-hover:text-brand-600">{album.title}</h3>
        {album.description && <p className="mt-1 line-clamp-2 text-sm text-muted">{album.description}</p>}
      </div>
    </Link>
  )
}

export function GallerySection({ section }) {
  const albums = section.data || []
  if (!albums.length) return null
  return (
    <section className="py-16">
      <div className="container-x">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
          <SectionHeading eyebrow={section.eyebrow} title={section.title} subtitle={section.subtitle} />
          {section.button_label && (
            <SmartLink href={section.button_url || '/gallery'} className="inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold text-brand-600">
              {section.button_label} <ArrowRight className="h-4 w-4" aria-hidden="true" />
            </SmartLink>
          )}
        </div>
        <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {albums.map((album) => <AlbumCard key={album.id} album={album} />)}
        </div>
      </div>
    </section>
  )
}

export function Testimonials({ section }) {
  const items = section.data || []
  const [index, setIndex] = useState(0)
  if (!items.length) return null
  const perView = 3
  const pages = Math.max(1, Math.ceil(items.length / perView))
  const visible = items.slice(index * perView, index * perView + perView)

  return (
    <section className="bg-brand-50/60 py-16">
      <div className="container-x">
        <SectionHeading eyebrow={section.eyebrow} title={section.title} subtitle={section.subtitle} align="center" />
        {/* Flex-wrap keeps one or two testimonials centred instead of stuck to the left of a 3-column grid. */}
        <div className="mt-10 flex flex-wrap justify-center gap-6" aria-live="polite">
          {visible.map((t) => (
            <figure key={t.id} className="card flex w-full flex-col p-6 md:w-[calc((100%-3rem)/3)]">
              <Quote className="h-8 w-8 fill-brand-100 text-brand-600" aria-hidden="true" />
              {t.rating > 0 && (
                <div className="mt-3 flex gap-0.5" aria-label={`${t.rating} out of 5 stars`}>
                  {Array.from({ length: 5 }).map((_, i) => (
                    <Star key={i} className={cn('h-4 w-4', i < t.rating ? 'fill-amber-400 text-amber-400' : 'text-slate-300')} aria-hidden="true" />
                  ))}
                </div>
              )}
              <blockquote className="mt-3 flex-1 text-[15px] leading-relaxed text-slate-700">“{t.quote}”</blockquote>
              <figcaption className="mt-5 flex items-center gap-3 border-t border-slate-100 pt-4">
                {t.photo_url ? (
                  <img src={t.photo_url} alt="" loading="lazy" className="h-11 w-11 rounded-full object-cover" />
                ) : (
                  <span className="icon-circle h-11 w-11 font-semibold">{t.name.charAt(0)}</span>
                )}
                <span>
                  <span className="block font-semibold text-navy-900">{t.name}</span>
                  <span className="block text-xs text-muted">{[t.designation, t.company].filter(Boolean).join(', ')}</span>
                </span>
              </figcaption>
            </figure>
          ))}
        </div>
        {pages > 1 && (
          <div className="mt-8 flex justify-center gap-2">
            {Array.from({ length: pages }).map((_, i) => (
              <button key={i} type="button" onClick={() => setIndex(i)} aria-label={`Show testimonials page ${i + 1}`} aria-current={i === index}
                className={cn('h-2 rounded-full transition-all', i === index ? 'w-8 bg-brand-600' : 'w-2 bg-slate-300 hover:bg-slate-400')} />
            ))}
          </div>
        )}
      </div>
    </section>
  )
}

export function TeamGrid({ section }) {
  const members = section.data || []
  if (!members.length) return null
  return (
    <section className="py-16">
      <div className="container-x">
        <SectionHeading eyebrow={section.eyebrow} title={section.title} subtitle={section.subtitle} align="center" />
        <ul className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          {members.map((m) => (
            <li key={m.id} className="card overflow-hidden text-center">
              <div className="aspect-[4/5] bg-brand-50">
                {m.photo_url && <img src={m.photo_url} alt={`Portrait of ${m.name}`} loading="lazy" className="h-full w-full object-cover" />}
              </div>
              <div className="p-5">
                <h3 className="font-semibold text-navy-900">{m.name}</h3>
                {m.designation && <p className="mt-1 text-sm text-brand-600">{m.designation}</p>}
              </div>
            </li>
          ))}
        </ul>
      </div>
    </section>
  )
}

export function VideoSection({ section }) {
  const [open, setOpen] = useState(false)
  const embed = youtubeEmbed(section.video_url)
  if (!section.video_url) return null
  const bg = section.settings?.background || 'light'
  const dark = bg === 'dark'

  return (
    <section className={cn('py-16', dark ? 'bg-navy-900 text-white' : bg === 'light' ? 'bg-brand-50/60' : 'bg-white')}>
      <div className="container-x">
        <div className={cn('mx-auto max-w-3xl text-center', dark && '[&_h2]:text-white [&_p]:text-white/80')}>
          <SectionHeading eyebrow={section.eyebrow} title={section.title} subtitle={section.subtitle} align="center" />
        </div>
        <div className="relative mx-auto mt-10 aspect-video max-w-4xl overflow-hidden rounded-2xl bg-navy-950 shadow-2xl">
          {section.image_url && <img src={section.image_url} alt={section.image_alt || ''} loading="lazy" className="h-full w-full object-cover opacity-80" />}
          <button
            type="button"
            onClick={() => (embed ? setOpen(true) : window.open(section.video_url, '_blank', 'noopener'))}
            className="absolute top-1/2 left-1/2 flex h-20 w-20 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-white text-brand-600 shadow-xl ring-8 ring-white/30 transition-transform hover:scale-105"
            aria-label={`Play video: ${section.title || 'video'}`}
          >
            <Play className="ml-1 h-8 w-8 fill-current" aria-hidden="true" />
          </button>
        </div>
      </div>
      {open && <VideoModal url={embed} onClose={() => setOpen(false)} />}
    </section>
  )
}
