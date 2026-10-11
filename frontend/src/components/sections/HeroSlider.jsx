import { useCallback, useEffect, useState } from 'react'
import { ArrowRight, ChevronLeft, ChevronRight } from 'lucide-react'
import SmartLink from '../ui/SmartLink'
import { cn } from '../../lib/utils'

export default function HeroSlider({ section }) {
  const slides = section.data || []
  const [index, setIndex] = useState(0)
  const [paused, setPaused] = useState(false)
  const autoplay = section.settings?.autoplay !== false
  const interval = Math.max(3, Number(section.settings?.interval) || 6) * 1000

  const go = useCallback((i) => setIndex((i + slides.length) % slides.length), [slides.length])

  useEffect(() => {
    const reduced = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
    if (!autoplay || paused || reduced || slides.length < 2) return
    const timer = setInterval(() => setIndex((i) => (i + 1) % slides.length), interval)
    return () => clearInterval(timer)
  }, [autoplay, paused, interval, slides.length])

  if (!slides.length) return null

  return (
    <section
      className="relative overflow-hidden bg-navy-950 text-white"
      aria-roledescription="carousel"
      aria-label="Highlights"
      onMouseEnter={() => setPaused(true)}
      onMouseLeave={() => setPaused(false)}
      onFocus={() => setPaused(true)}
      onBlur={() => setPaused(false)}
    >
      <div className="relative min-h-[520px] lg:min-h-[600px]">
        {slides.map((slide, i) => (
          <div
            key={slide.id}
            className={cn('absolute inset-0 transition-opacity duration-700', i === index ? 'opacity-100' : 'pointer-events-none opacity-0')}
            aria-hidden={i !== index}
            role="group"
            aria-roledescription="slide"
            aria-label={`${i + 1} of ${slides.length}`}
          >
            {slide.image_url && <img src={slide.image_url} alt="" className="absolute inset-0 h-full w-full object-cover" fetchPriority={i === 0 ? 'high' : 'low'} />}
            <div className="absolute inset-0 bg-gradient-to-r from-navy-950 via-navy-950/85 to-navy-950/10" aria-hidden="true" />
            <div className="container-x relative flex min-h-[520px] items-center py-16 lg:min-h-[600px]">
              <div className="max-w-xl">
                {slide.eyebrow && <p className="text-xs font-semibold tracking-[0.16em] text-white/90 uppercase">{slide.eyebrow}</p>}
                {i === 0 ? (
                  <h1 className="mt-4 text-4xl leading-[1.1] font-bold sm:text-5xl lg:text-6xl">
                    {slide.title} {slide.highlight && <span className="block text-brand-500">{slide.highlight}</span>}
                    {slide.title_suffix && <span className="block text-3xl sm:text-4xl lg:text-5xl">{slide.title_suffix}</span>}
                  </h1>
                ) : (
                  <h2 className="mt-4 text-4xl leading-[1.1] font-bold sm:text-5xl lg:text-6xl">
                    {slide.title} {slide.highlight && <span className="block text-brand-500">{slide.highlight}</span>}
                    {slide.title_suffix && <span className="block text-3xl sm:text-4xl lg:text-5xl">{slide.title_suffix}</span>}
                  </h2>
                )}
                {slide.subtitle && <p className="mt-5 max-w-lg text-base text-white/85">{slide.subtitle}</p>}
                <div className="mt-8 flex flex-wrap gap-3">
                  {slide.primary_label && (
                    <SmartLink href={slide.primary_url} className="btn-primary px-6 py-3" tabIndex={i === index ? 0 : -1}>
                      {slide.primary_label} <ArrowRight className="h-4 w-4" aria-hidden="true" />
                    </SmartLink>
                  )}
                  {slide.secondary_label && (
                    <SmartLink href={slide.secondary_url} className="btn-outline-light px-6 py-3" tabIndex={i === index ? 0 : -1}>
                      {slide.secondary_label}
                    </SmartLink>
                  )}
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      {slides.length > 1 && (
        <>
          <button type="button" onClick={() => go(index - 1)} className="absolute top-1/2 left-3 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/40 text-white hover:bg-white/10 md:flex" aria-label="Previous slide">
            <ChevronLeft className="h-5 w-5" aria-hidden="true" />
          </button>
          <button type="button" onClick={() => go(index + 1)} className="absolute top-1/2 right-3 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/40 text-white hover:bg-white/10 md:flex" aria-label="Next slide">
            <ChevronRight className="h-5 w-5" aria-hidden="true" />
          </button>
          <div className="container-x absolute inset-x-0 bottom-6 flex gap-2">
            {slides.map((s, i) => (
              <button
                key={s.id}
                type="button"
                onClick={() => go(i)}
                aria-label={`Go to slide ${i + 1}`}
                aria-current={i === index}
                className={cn('h-1.5 rounded-full transition-all', i === index ? 'w-8 bg-brand-500' : 'w-3 bg-white/50 hover:bg-white')}
              />
            ))}
          </div>
        </>
      )}
    </section>
  )
}
