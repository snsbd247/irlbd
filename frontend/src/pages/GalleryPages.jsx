import { useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ArrowLeft, Images } from 'lucide-react'
import Seo from '../components/Seo'
import PageHero from '../components/layout/PageHero'
import { AlbumCard, Lightbox } from '../components/sections/ShowcaseSections'
import { CardGridSkeleton, EmptyState, ErrorState, PageLoader } from '../components/ui/States'
import { api } from '../lib/api'
import { formatDate } from '../lib/utils'
import { NotFound } from './PublicPages'

const get = (url) => api.get(url).then((r) => r.data)

/** Album grid rendered inside the "Gallery" CMS page (template: gallery). */
export function GalleryModule() {
  const { data: albums, isLoading, isError, refetch } = useQuery({ queryKey: ['gallery'], queryFn: () => get('/gallery') })
  return (
    <section className="py-14">
      <div className="container-x">
        {isLoading ? (
          <CardGridSkeleton />
        ) : isError ? (
          <ErrorState onRetry={refetch} />
        ) : !albums.length ? (
          <EmptyState icon={Images} title="No albums yet" message="Photo albums will appear here soon." />
        ) : (
          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {albums.map((album) => <AlbumCard key={album.id} album={album} />)}
          </div>
        )}
      </div>
    </section>
  )
}

export function AlbumPage() {
  const { slug } = useParams()
  const { data: album, isLoading, isError, error, refetch } = useQuery({ queryKey: ['gallery', slug], queryFn: () => get(`/gallery/${encodeURIComponent(slug)}`) })
  const [open, setOpen] = useState(null)

  if (isLoading) return <PageLoader />
  if (error?.response?.status === 404) return <NotFound />
  if (isError) return <div className="container-x py-20"><ErrorState onRetry={refetch} /></div>

  const images = album.images || []
  return (
    <>
      <Seo title={album.title} description={album.description} image={album.cover_image_url} />
      <PageHero title={album.title} subtitle={album.description} image={album.cover_image_url} crumbs={[{ label: 'Gallery', href: '/gallery' }, { label: album.title }]}>
        <p className="mt-3 text-sm text-white/80">
          {images.length} photo{images.length === 1 ? '' : 's'}{album.event_date ? ` · ${formatDate(album.event_date)}` : ''}
        </p>
      </PageHero>
      <section className="py-12">
        <div className="container-x">
          {images.length === 0 ? (
            <EmptyState icon={Images} title="This album is empty" />
          ) : (
            <ul className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
              {images.map((img, i) => (
                <li key={img.id}>
                  <button type="button" onClick={() => setOpen(i)} className="group block w-full overflow-hidden rounded-xl bg-brand-50 text-left" aria-label={`Open photo ${i + 1}${img.caption ? `: ${img.caption}` : ''}`}>
                    <img src={img.url} alt={img.caption || ''} loading="lazy" className="aspect-square w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                  </button>
                  {img.caption && <p className="mt-1.5 truncate text-xs text-muted">{img.caption}</p>}
                </li>
              ))}
            </ul>
          )}
          <Link to="/gallery" className="mt-10 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600">
            <ArrowLeft className="h-4 w-4" aria-hidden="true" /> All albums
          </Link>
        </div>
      </section>
      {open !== null && <Lightbox images={images} index={open} onIndex={setOpen} onClose={() => setOpen(null)} />}
    </>
  )
}
