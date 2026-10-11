import { useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { ArrowRight, ChevronDown, ChevronLeft, ChevronRight, Quote } from 'lucide-react'
import SectionHeading from '../ui/SectionHeading'
import SmartLink from '../ui/SmartLink'
import { IconBadge, MiniProductCard, NewsRowCard } from '../ui/Cards'
import { Icon } from '../../lib/icons'
import { cn, safeHref } from '../../lib/utils'
import { RichText } from './ContentSections'

function HeaderRow({ section, align = 'left' }) {
  return (
    <div className={cn('flex flex-col gap-4 sm:flex-row sm:items-end', align === 'center' ? 'sm:justify-center' : 'sm:justify-between')}>
      <SectionHeading eyebrow={section.eyebrow} title={section.title} subtitle={section.subtitle} align={align} />
      {section.button_label && (
        <SmartLink href={section.button_url} className="inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold text-brand-600 hover:gap-2.5">
          {section.button_label} <ArrowRight className="h-4 w-4 transition-all" aria-hidden="true" />
        </SmartLink>
      )}
    </div>
  )
}

export function ServicesGrid({ section }) {
  const services = section.data || []
  if (!services.length) return null
  return (
    <section className="bg-brand-50/60 py-16">
      <div className="container-x">
        <SectionHeading eyebrow={section.eyebrow} title={section.title} subtitle={section.subtitle} align="center" />
        <div className="mt-10 grid gap-5 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6">
          {services.map((service) => (
            <article key={service.id} className="card flex flex-col p-6">
              <Icon name={service.icon} className="h-9 w-9 text-brand-600" />
              <h3 className="mt-4 text-[15px] leading-snug font-semibold text-navy-900">{service.title}</h3>
              {service.short_description && <p className="mt-2 text-sm text-muted">{service.short_description}</p>}
              <Link to={`/services/${service.slug}`} className="mt-auto inline-flex items-center gap-1.5 pt-5 text-sm font-semibold text-brand-600" aria-label={`Learn more about ${service.title}`}>
                Learn More <ArrowRight className="h-4 w-4" aria-hidden="true" />
              </Link>
            </article>
          ))}
        </div>
      </div>
    </section>
  )
}

export function ProductCategories({ section }) {
  const categories = section.data || []
  if (!categories.length) return null
  const banners = section.settings?.layout === 'banners'

  if (banners) {
    return (
      <section className="py-10">
        <div className="container-x">
          {(section.title || section.eyebrow) && <HeaderRow section={section} />}
          <div className={cn('grid gap-6 lg:grid-cols-3', (section.title || section.eyebrow) && 'mt-8')}>
            {categories.map((cat) => (
              <article key={cat.id} className="relative flex min-h-[360px] flex-col overflow-hidden rounded-2xl bg-gradient-to-b from-brand-50 to-brand-100/70">
                <div className="relative z-10 flex gap-4 p-6">
                  <IconBadge name={cat.icon} size="lg" className="bg-white" />
                  <div>
                    <h3 className="text-lg font-semibold text-navy-900">{cat.name}</h3>
                    {cat.short_description && <p className="mt-1 text-sm text-muted">{cat.short_description}</p>}
                    <Link to={`/products?category=${cat.slug}`} className="btn-primary mt-4 py-2" aria-label={`View ${cat.name} products`}>
                      View Products <ArrowRight className="h-4 w-4" aria-hidden="true" />
                    </Link>
                  </div>
                </div>
                {cat.image_url && <img src={cat.image_url} alt="" loading="lazy" className="mt-auto h-48 w-full object-cover object-top" />}
              </article>
            ))}
          </div>
        </div>
      </section>
    )
  }

  return (
    <section className="py-16">
      <div className="container-x">
        <HeaderRow section={section} />
        <div className="mt-8 grid gap-6 md:grid-cols-3">
          {categories.map((cat) => (
            <article key={cat.id} className="card group overflow-hidden">
              <Link to={`/products?category=${cat.slug}`} className="block aspect-[16/10] overflow-hidden bg-brand-50" tabIndex={-1} aria-hidden="true">
                {cat.image_url && <img src={cat.image_url} alt="" loading="lazy" className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />}
              </Link>
              <div className="flex items-center gap-4 p-5">
                <IconBadge name={cat.icon} size="sm" />
                <div className="min-w-0 flex-1">
                  <h3 className="font-semibold text-navy-900">{cat.name}</h3>
                  {cat.short_description && <p className="mt-0.5 line-clamp-2 text-xs text-muted">{cat.short_description}</p>}
                </div>
                <Link to={`/products?category=${cat.slug}`} className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white hover:bg-brand-700" aria-label={`View ${cat.name}`}>
                  <ChevronRight className="h-4 w-4" aria-hidden="true" />
                </Link>
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  )
}

export function FeaturedProducts({ section }) {
  const products = section.data || []
  if (!products.length) return null
  return (
    <section className="py-12">
      <div className="container-x">
        <div className="flex items-end justify-between gap-4">
          <div>
            {section.eyebrow && <p className="eyebrow">{section.eyebrow}</p>}
            <h2 className="mt-1 flex items-center gap-3 text-xl font-bold text-navy-900 sm:text-2xl">
              {!section.eyebrow && <span className="h-0.5 w-8 bg-brand-600" aria-hidden="true" />}
              {section.title || 'Featured Products'}
            </h2>
          </div>
          <SmartLink href={section.button_url || '/products'} className="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600">
            {section.button_label || 'View All Products'} <ArrowRight className="h-4 w-4" aria-hidden="true" />
          </SmartLink>
        </div>
        <div className="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
          {products.map((product) => (
            <MiniProductCard key={product.id} product={product} />
          ))}
        </div>
      </div>
    </section>
  )
}

export function Clients({ section }) {
  const clients = section.data || []
  const scroller = useRef(null)
  if (!clients.length) return null
  const scroll = (dir) => scroller.current?.scrollBy({ left: dir * 260, behavior: 'smooth' })

  return (
    <section className="bg-brand-50/50 py-14">
      <div className="container-x">
        <SectionHeading eyebrow={section.eyebrow} title={section.title} subtitle={section.subtitle} align="center" />
        <div className="relative mt-8 flex items-center gap-2">
          <button type="button" onClick={() => scroll(-1)} className="hidden h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-navy-900 hover:bg-brand-50 sm:flex" aria-label="Scroll clients left">
            <ChevronLeft className="h-4 w-4" aria-hidden="true" />
          </button>
          <ul ref={scroller} className="flex flex-1 snap-x gap-4 overflow-x-auto scroll-smooth pb-2 [scrollbar-width:none]">
            {clients.map((client) => {
              const logo = client.logo_url ? <img src={client.logo_url} alt={client.name} loading="lazy" className="max-h-12 w-auto max-w-full object-contain grayscale transition hover:grayscale-0" /> : <span className="font-semibold text-slate-500">{client.name}</span>
              return (
                <li key={client.id} className="flex h-20 min-w-[180px] snap-start items-center justify-center rounded-lg bg-white px-5">
                  {safeHref(client.website_url) ? (
                    <a href={safeHref(client.website_url)} target="_blank" rel="noopener noreferrer" aria-label={client.name}>{logo}</a>
                  ) : (
                    logo
                  )}
                </li>
              )
            })}
          </ul>
          <button type="button" onClick={() => scroll(1)} className="hidden h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-navy-900 hover:bg-brand-50 sm:flex" aria-label="Scroll clients right">
            <ChevronRight className="h-4 w-4" aria-hidden="true" />
          </button>
        </div>
      </div>
    </section>
  )
}

export function LatestNews({ section }) {
  const posts = section.data || []
  if (!posts.length) return null
  return (
    <section className="py-16">
      <div className="container-x">
        <HeaderRow section={section} />
        <div className="mt-8 grid gap-5 lg:grid-cols-3">
          {posts.map((post) => (
            <NewsRowCard key={post.id} post={post} />
          ))}
        </div>
      </div>
    </section>
  )
}

export function FounderMessage({ section }) {
  const founder = section.data
  if (!founder) return null
  return (
    <section className="py-16">
      <div className="container-x grid gap-12 lg:grid-cols-[1.25fr_1fr]">
        <div>
          {section.eyebrow && <p className="eyebrow eyebrow-center">{section.eyebrow}</p>}
          <h2 className="heading-lg mt-2">{founder.name}</h2>
          {founder.designation && <p className="mt-1 text-sm font-semibold tracking-wider text-brand-600 uppercase">{founder.designation}</p>}
          <RichText html={founder.message} className="mt-5 text-[15px]" />
          <div className="mt-6">
            {founder.signature_url && <img src={founder.signature_url} alt={`Signature of ${founder.name}`} className="h-14 w-auto" loading="lazy" />}
            <p className="mt-1 font-semibold text-navy-900">{founder.name}</p>
            {founder.designation && <p className="text-sm text-muted">{founder.designation}</p>}
          </div>
        </div>
        <div className="relative">
          <div className="absolute -top-4 right-0 h-24 w-24 bg-[radial-gradient(#b6d6ff_2px,transparent_2px)] [background-size:12px_12px]" aria-hidden="true" />
          {founder.photo_url && (
            <img src={founder.photo_url} alt={`Portrait of ${founder.name}`} loading="lazy" className="relative mx-auto aspect-[6/7] w-full max-w-md rounded-2xl object-cover shadow-xl" />
          )}
          {founder.quote && (
            <figure className="relative -mt-10 rounded-2xl bg-brand-50 p-8 shadow-sm lg:mx-0">
              <Quote className="h-9 w-9 fill-brand-600 text-brand-600" aria-hidden="true" />
              <blockquote className="mt-3 text-lg leading-relaxed text-slate-700 italic">{founder.quote}</blockquote>
              <figcaption className="mt-4 text-right text-sm font-medium text-navy-900">— {founder.name}</figcaption>
            </figure>
          )}
        </div>
      </div>
    </section>
  )
}

export function Timeline({ section }) {
  const milestones = section.data || []
  return (
    <section className="py-16">
      <div className="container-x">
        <div className="grid gap-8 lg:grid-cols-[1.6fr_1fr] lg:items-start">
          <div>
            <SectionHeading eyebrow={section.eyebrow} title={section.title} />
            <RichText html={section.content} className="mt-4 text-[15px]" />
          </div>
          {section.subtitle && (
            <div className="flex gap-4 rounded-2xl bg-brand-50 p-6">
              <IconBadge name="trophy" size="lg" className="bg-white" />
              <p className="text-sm leading-relaxed text-slate-700">{section.subtitle}</p>
            </div>
          )}
        </div>

        {milestones.length > 0 && (
          <ol className="relative mt-14 space-y-10 lg:space-y-0 lg:before:absolute lg:before:inset-y-0 lg:before:left-1/2 lg:before:w-0.5 lg:before:bg-brand-500">
            {milestones.map((m, i) => {
              const right = i % 2 === 1
              return (
                <li key={m.id} className="relative lg:grid lg:grid-cols-2 lg:gap-16 lg:py-5">
                  <span className="absolute top-1/2 left-1/2 hidden h-4 w-4 -translate-x-1/2 -translate-y-1/2 rounded-full border-4 border-brand-500 bg-white lg:block" aria-hidden="true" />
                  <div className={cn('flex flex-col gap-5 sm:flex-row sm:items-center', right && 'lg:col-start-2')}>
                    {m.image_url && (
                      <img src={m.image_url} alt="" loading="lazy" className={cn('aspect-[16/10] w-full rounded-xl object-cover shadow-md sm:w-56 sm:shrink-0', right && 'sm:order-2')} />
                    )}
                    <MilestoneText m={m} />
                  </div>
                </li>
              )
            })}
          </ol>
        )}
      </div>
    </section>
  )
}

function MilestoneText({ m }) {
  return (
    <div>
      <p className="text-3xl font-bold text-brand-600">{m.year}</p>
      <h3 className="mt-1 text-lg font-semibold text-navy-900">{m.title}</h3>
      {m.description && <p className="mt-2 text-sm leading-relaxed text-muted">{m.description}</p>}
    </div>
  )
}

export function Certifications({ section }) {
  const certs = section.data || []
  // Only admin-verified certifications are ever returned; hide the block when none exist.
  if (!certs.length) return null
  return (
    <section className="py-12">
      <div className="container-x grid items-center gap-8 lg:grid-cols-2">
        <SectionHeading eyebrow={section.eyebrow} title={section.title} subtitle={section.subtitle} />
        <ul className="flex flex-wrap items-center justify-center gap-6 rounded-2xl bg-brand-50/70 px-6 py-6 lg:justify-around">
          {certs.map((c) => (
            <li key={c.id} title={c.description || c.name}>
              {c.logo_url ? <img src={c.logo_url} alt={c.name} loading="lazy" className="h-16 w-auto object-contain" /> : <span className="font-semibold text-navy-900">{c.name}</span>}
            </li>
          ))}
        </ul>
      </div>
    </section>
  )
}

export function Faq({ section }) {
  const faqs = section.data || []
  const [open, setOpen] = useState(null)
  if (!faqs.length) return null
  return (
    <section className="py-14">
      <div className="container-x max-w-4xl">
        <SectionHeading eyebrow={section.eyebrow} title={section.title} />
        <div className="mt-6 space-y-3">
          {faqs.map((faq) => {
            const isOpen = open === faq.id
            return (
              <div key={faq.id} className="card overflow-hidden">
                <h3>
                  <button
                    type="button"
                    className="flex w-full items-center justify-between gap-4 px-5 py-4 text-left text-sm font-medium text-navy-900 hover:bg-slate-50"
                    aria-expanded={isOpen}
                    aria-controls={`faq-${faq.id}`}
                    onClick={() => setOpen(isOpen ? null : faq.id)}
                  >
                    {faq.question}
                    <ChevronDown className={cn('h-4 w-4 shrink-0 transition-transform', isOpen && 'rotate-180')} aria-hidden="true" />
                  </button>
                </h3>
                <div id={`faq-${faq.id}`} hidden={!isOpen} className="px-5 pb-5 text-sm leading-relaxed whitespace-pre-line text-muted">
                  {faq.answer}
                </div>
              </div>
            )
          })}
        </div>
      </div>
    </section>
  )
}

