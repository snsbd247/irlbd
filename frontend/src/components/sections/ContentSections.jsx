import { useState } from 'react'
import { ArrowRight, ChartColumn, CircleCheck, Play } from 'lucide-react'
import SectionHeading from '../ui/SectionHeading'
import SmartLink from '../ui/SmartLink'
import { IconBadge, VideoModal } from '../ui/Cards'
import { Icon } from '../../lib/icons'
import { useSettings } from '../../lib/queries'
import { cn, safeHtml, youtubeEmbed } from '../../lib/utils'
import NewsletterForm from '../layout/NewsletterForm'

function RichText({ html, className }) {
  if (!html) return null
  return <div className={cn('rich-text', className)} dangerouslySetInnerHTML={safeHtml(html)} />
}

function PlayableImage({ src, alt, videoUrl, className, children }) {
  const [open, setOpen] = useState(false)
  const embed = youtubeEmbed(videoUrl)
  return (
    <div className={cn('relative overflow-hidden rounded-2xl bg-brand-50', className)}>
      {src && <img src={src} alt={alt || ''} loading="lazy" className="h-full w-full object-cover" />}
      {videoUrl && (
        <button
          type="button"
          onClick={() => (embed ? setOpen(true) : window.open(videoUrl, '_blank', 'noopener'))}
          className="absolute top-1/2 left-1/2 flex h-16 w-16 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-white text-brand-600 shadow-xl ring-8 ring-white/30 transition-transform hover:scale-105"
          aria-label="Play video"
        >
          <Play className="ml-1 h-6 w-6 fill-current" aria-hidden="true" />
        </button>
      )}
      {children}
      {open && <VideoModal url={embed} onClose={() => setOpen(false)} />}
    </div>
  )
}

export function FeatureStrip({ section }) {
  const items = section.items || []
  if (!items.length) return null
  return (
    <section className="border-b border-brand-100 bg-brand-50/70">
      <div className="container-x grid grid-cols-2 gap-6 py-6 lg:grid-cols-4">
        {items.map((item) => (
          <div key={item.id} className="flex items-center justify-center gap-3 sm:justify-start lg:justify-center">
            <IconBadge name={item.icon} size="sm" className="bg-white" />
            <div>
              <p className="text-sm font-semibold text-navy-900">{item.title}</p>
              {item.subtitle && <p className="text-xs text-muted">{item.subtitle}</p>}
            </div>
          </div>
        ))}
      </div>
    </section>
  )
}

export function AboutIntro({ section }) {
  const s = section.settings || {}
  return (
    <section className="py-16 lg:py-20">
      <div className="container-x grid items-center gap-12 lg:grid-cols-2">
        <PlayableImage src={section.image_url} alt={section.image_alt} videoUrl={section.video_url} className="aspect-[4/3] shadow-xl">
          {s.badge_value && (
            <div className="absolute bottom-5 left-5 flex items-center gap-3 rounded-xl bg-white px-5 py-4 shadow-lg">
              <ChartColumn className="h-9 w-9 text-brand-600" aria-hidden="true" />
              <div>
                <p className="text-2xl leading-none font-bold text-navy-900">{s.badge_value}</p>
                {s.badge_label && <p className="mt-1 text-xs text-muted">{s.badge_label}</p>}
              </div>
            </div>
          )}
        </PlayableImage>
        <div>
          <SectionHeading eyebrow={section.eyebrow} title={section.title} />
          <RichText html={section.content} className="mt-5 text-[15px]" />
          {section.button_label && (
            <SmartLink href={section.button_url} className="btn-primary mt-6">
              {section.button_label} <ArrowRight className="h-4 w-4" aria-hidden="true" />
            </SmartLink>
          )}
          {section.items?.length > 0 && (
            <dl className="mt-8 grid gap-4 sm:grid-cols-3">
              {section.items.map((item) => (
                <div key={item.id} className="card flex items-center gap-3 px-4 py-4">
                  <Icon name={item.icon} className="h-8 w-8 shrink-0 text-brand-600" />
                  <div>
                    <dd className="text-xl leading-none font-bold text-navy-900">{item.value}</dd>
                    <dt className="mt-1 text-xs text-muted">{item.title}</dt>
                  </div>
                </div>
              ))}
            </dl>
          )}
        </div>
      </div>
    </section>
  )
}

export function ProcessSteps({ section }) {
  const items = section.items || []
  const light = section.settings?.background === 'light'
  return (
    <section className={cn('py-16', light ? 'bg-brand-50/60' : 'bg-white')}>
      <div className="container-x">
        <SectionHeading eyebrow={section.eyebrow} title={section.title} subtitle={section.subtitle} align="center" />
        <ol className="mt-12 grid grid-cols-2 gap-8 sm:grid-cols-3 lg:flex lg:items-start lg:justify-between lg:gap-2">
          {items.map((item, i) => (
            <li key={item.id} className="relative flex flex-col items-center text-center lg:flex-1">
              <span className="icon-circle h-16 w-16 border border-brand-100 bg-white shadow-sm [&>svg]:h-7 [&>svg]:w-7">
                <Icon name={item.icon} />
              </span>
              <span className="mt-3 text-sm font-semibold text-brand-600">{String(i + 1).padStart(2, '0')}</span>
              <span className="mt-1 text-sm font-semibold text-navy-900">{item.title}</span>
              {item.description && <span className="mt-1 max-w-48 text-xs text-muted">{item.description}</span>}
              {i < items.length - 1 && (
                <ArrowRight className="absolute top-6 -right-3 hidden h-5 w-5 text-slate-400 lg:block" aria-hidden="true" />
              )}
            </li>
          ))}
        </ol>
      </div>
    </section>
  )
}

function ItemLink({ item, children, className }) {
  return item.link_url ? (
    <SmartLink href={item.link_url} className={cn(className, 'transition-shadow hover:shadow-lg')}>
      {children}
    </SmartLink>
  ) : (
    <div className={className}>{children}</div>
  )
}

export function IconCards({ section }) {
  const items = section.items || []
  const style = section.settings?.style || 'cards'
  const columns = Number(section.settings?.columns) || Math.min(items.length, 4)
  const gridCols = { 2: 'lg:grid-cols-2', 3: 'lg:grid-cols-3', 4: 'lg:grid-cols-4', 5: 'lg:grid-cols-5', 6: 'lg:grid-cols-6' }[columns] || 'lg:grid-cols-4'

  if (style === 'strip') {
    return (
      <section className="py-10">
        <div className="container-x">
          {(section.title || section.eyebrow) && <SectionHeading eyebrow={section.eyebrow} title={section.title} subtitle={section.subtitle} align="center" className="mb-8" />}
          <div className="grid gap-6 rounded-2xl bg-brand-50/70 px-6 py-8 sm:grid-cols-2 lg:grid-cols-4 lg:divide-x lg:divide-brand-100">
            {items.map((item) => (
              <ItemLink key={item.id} item={item} className="flex items-center gap-4 lg:px-5">
                <IconBadge name={item.icon} size="lg" className="bg-white" />
                <div>
                  <h3 className="font-semibold text-navy-900">{item.title}</h3>
                  {item.description && <p className="mt-1 text-sm text-muted">{item.description}</p>}
                </div>
              </ItemLink>
            ))}
          </div>
        </div>
      </section>
    )
  }

  if (style === 'split') {
    return (
      <section className="py-16">
        <div className="container-x grid items-center gap-10 lg:grid-cols-2">
          <SectionHeading eyebrow={section.eyebrow} title={section.title} subtitle={section.subtitle} />
          <div className="grid grid-cols-2 gap-6 sm:grid-cols-4 sm:divide-x sm:divide-slate-200">
            {items.map((item) => (
              <ItemLink key={item.id} item={item} className="flex flex-col items-center px-2 text-center">
                <IconBadge name={item.icon} size="lg" />
                <h3 className="mt-3 text-sm font-semibold text-navy-900">{item.title}</h3>
                {item.description && <p className="mt-1 text-xs text-muted">{item.description}</p>}
              </ItemLink>
            ))}
          </div>
        </div>
      </section>
    )
  }

  return (
    <section className="py-16">
      <div className="container-x">
        <div className="rounded-3xl bg-brand-50/60 px-5 py-12 sm:px-10">
          <SectionHeading eyebrow={section.eyebrow} title={section.title} subtitle={section.subtitle} align="center" />
          <div className={cn('mt-10 grid gap-5 sm:grid-cols-2', gridCols)}>
            {items.map((item) => (
              <ItemLink key={item.id} item={item} className="card flex flex-col items-center px-5 py-8 text-center">
                <IconBadge name={item.icon} size="lg" />
                <h3 className="mt-4 font-semibold text-navy-900">{item.title}</h3>
                {item.description && <p className="mt-2 text-sm text-muted">{item.description}</p>}
              </ItemLink>
            ))}
          </div>
        </div>
      </div>
    </section>
  )
}

export function Stats({ section }) {
  const items = section.items || []
  return (
    <section className="py-10">
      <div className="container-x">
        <dl className="grid gap-6 rounded-2xl bg-brand-50/70 px-6 py-8 sm:grid-cols-2 lg:grid-cols-4 lg:divide-x lg:divide-brand-100">
          {items.map((item) => (
            <div key={item.id} className="flex items-center gap-4 lg:px-6">
              <IconBadge name={item.icon} size="lg" className="bg-white" />
              <div>
                <dd className="text-2xl font-bold text-navy-900">{item.value}</dd>
                <dt className="text-sm text-muted">{item.title}</dt>
              </div>
            </div>
          ))}
        </dl>
      </div>
    </section>
  )
}

function ItemsBlock({ items, style }) {
  if (!items?.length) return null
  if (style === 'checklist') {
    return (
      <ul className="mt-6 grid gap-x-8 gap-y-3 sm:grid-cols-2">
        {items.map((item) => (
          <li key={item.id} className="flex items-start gap-2.5 text-sm text-ink">
            <CircleCheck className="mt-0.5 h-5 w-5 shrink-0 fill-brand-600 text-white" aria-hidden="true" />
            {item.title}
          </li>
        ))}
      </ul>
    )
  }
  if (style === 'icons_row') {
    return (
      <ul className="mt-8 grid grid-cols-2 gap-6 sm:grid-cols-4 sm:divide-x sm:divide-slate-200">
        {items.map((item) => (
          <li key={item.id} className="flex flex-col items-center px-2 text-center">
            <Icon name={item.icon} className="h-10 w-10 text-brand-600" />
            <span className="mt-3 text-sm font-medium text-navy-900">{item.title}</span>
            {item.description && <span className="text-xs text-muted">{item.description}</span>}
          </li>
        ))}
      </ul>
    )
  }
  // inline
  return (
    <ul className="mt-8 grid gap-5 sm:grid-cols-3 sm:divide-x sm:divide-slate-200">
      {items.map((item) => (
        <li key={item.id} className="flex items-center gap-3 sm:px-4 sm:first:pl-0">
          <Icon name={item.icon} className="h-9 w-9 shrink-0 text-brand-600" />
          <span>
            <span className="block text-sm font-semibold text-navy-900">{item.title}</span>
            {item.description && <span className="block text-xs text-muted">{item.description}</span>}
          </span>
        </li>
      ))}
    </ul>
  )
}

export function ImageText({ section }) {
  const s = section.settings || {}
  const imageLeft = s.image_position === 'left'
  const style = s.item_style || (section.items?.length ? 'inline' : null)
  const sideCards = style === 'side_cards' && section.items?.length > 0

  const media = (
    <div className={cn('relative', sideCards && 'grid gap-4 sm:grid-cols-[1fr_auto] sm:items-center')}>
      {section.image_url && (
        <PlayableImage src={section.image_url} alt={section.image_alt} videoUrl={section.video_url} className={cn('shadow-xl', sideCards ? 'aspect-[4/5] sm:aspect-auto sm:h-full' : 'aspect-[4/3]')} />
      )}
      {s.card_title && (
        <div className="relative mx-4 -mt-16 rounded-xl bg-white p-6 shadow-xl sm:absolute sm:top-1/2 sm:right-0 sm:mx-0 sm:mt-0 sm:w-80 sm:-translate-y-1/2 lg:-right-8">
          <div className="flex items-start gap-4">
            <Icon name={s.card_icon || 'target'} className="h-12 w-12 shrink-0 text-brand-600" />
            <div>
              <p className="text-xs font-semibold tracking-wider text-brand-600 uppercase">{s.card_title}</p>
              {s.card_text && <p className="mt-2 text-sm leading-relaxed text-navy-900">{s.card_text}</p>}
            </div>
          </div>
        </div>
      )}
      {sideCards && (
        <ul className="relative z-10 space-y-3 sm:-ml-14 sm:w-72">
          {section.items.map((item) => (
            <li key={item.id} className="card flex items-center gap-4 p-4">
              <IconBadge name={item.icon} size="md" />
              <div>
                <p className="text-sm font-semibold text-navy-900">{item.title}</p>
                {item.description && <p className="mt-0.5 text-xs text-muted">{item.description}</p>}
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  )

  return (
    <section className={cn('py-16', s.background === 'light' && 'bg-brand-50/60')}>
      <div className={cn('container-x grid items-center gap-12', section.image_url || sideCards ? 'lg:grid-cols-2' : '')}>
        <div className={cn(imageLeft && 'lg:order-2')}>
          <SectionHeading eyebrow={section.eyebrow} title={section.title} />
          <RichText html={section.content} className="mt-5 text-[15px]" />
          {!sideCards && <ItemsBlock items={section.items} style={style} />}
          {section.button_label && (
            <SmartLink href={section.button_url} className="btn-primary mt-7">
              {section.button_label} <ArrowRight className="h-4 w-4" aria-hidden="true" />
            </SmartLink>
          )}
        </div>
        {(section.image_url || sideCards) && <div className={cn(imageLeft && 'lg:order-1')}>{media}</div>}
      </div>
    </section>
  )
}

export function RichTextSection({ section }) {
  return (
    <section className="py-14">
      <div className="container-x max-w-4xl">
        <SectionHeading eyebrow={section.eyebrow} title={section.title} className="mb-6" />
        <RichText html={section.content} />
      </div>
    </section>
  )
}

export function CtaBanner({ section }) {
  const { data: s = {} } = useSettings()
  const title = section.title || s.cta_title
  const subtitle = section.subtitle || s.cta_subtitle
  const label = section.button_label || s.cta_button_label
  const url = section.button_url || s.cta_button_url || '/contact'
  const image = section.image_url || s.cta_background_url
  if (!title) return null

  return (
    <section className="relative overflow-hidden bg-brand-700 text-white">
      {image && <img src={image} alt="" aria-hidden="true" loading="lazy" className="absolute inset-0 h-full w-full object-cover opacity-25 mix-blend-luminosity" />}
      <div className="absolute inset-0 bg-gradient-to-r from-brand-800/95 via-brand-700/85 to-brand-600/60" aria-hidden="true" />
      <div className="container-x relative flex flex-col items-start gap-6 py-12 md:flex-row md:items-center md:justify-between">
        <div className="max-w-2xl">
          {section.eyebrow && <p className="text-xs font-semibold tracking-[0.14em] text-white/90 uppercase">— {section.eyebrow}</p>}
          <h2 className="mt-1 text-2xl font-bold sm:text-3xl">{title}</h2>
          {subtitle && <p className="mt-2 text-white/90">{subtitle}</p>}
        </div>
        {label && (
          <SmartLink href={url} className="btn-white shrink-0 px-7 py-3">
            {label} <ArrowRight className="h-4 w-4" aria-hidden="true" />
          </SmartLink>
        )}
      </div>
    </section>
  )
}

export function NewsletterSection({ section }) {
  const { data: s = {} } = useSettings()
  return (
    <section className="py-14">
      <div className="container-x">
        <div className="flex flex-col gap-6 rounded-2xl bg-navy-900 px-6 py-10 text-white md:flex-row md:items-center md:justify-between md:px-12">
          <div className="max-w-lg">
            <h2 className="text-2xl font-bold">{section.title || s.newsletter_title || 'Newsletter'}</h2>
            <p className="mt-2 text-white/80">{section.subtitle || s.newsletter_text}</p>
          </div>
          <div className="w-full max-w-md">
            <NewsletterForm dark idPrefix={`section-${section.id}`} />
          </div>
        </div>
      </div>
    </section>
  )
}

export { RichText }
