import DOMPurify from 'dompurify'

export const cn = (...classes) => classes.filter(Boolean).join(' ')

/** Date locale follows the website language (<html lang>), e.g. Bengali digits for "bn". */
const dateLocale = () => (typeof document !== 'undefined' && document.documentElement.lang === 'bn' ? 'bn-BD' : 'en-GB')

export function formatDate(value, options = { day: '2-digit', month: 'short', year: 'numeric' }) {
  if (!value) return ''
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? '' : date.toLocaleDateString(dateLocale(), options)
}

export function dateParts(value) {
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return null
  return {
    day: date.toLocaleDateString(dateLocale(), { day: '2-digit' }),
    month: date.toLocaleDateString(dateLocale(), { month: 'short' }).toUpperCase(),
    year: date.toLocaleDateString(dateLocale(), { year: 'numeric' }),
  }
}

/**
 * Rich text is sanitized on the server; this is defence in depth before rendering.
 */
export function safeHtml(html) {
  return { __html: DOMPurify.sanitize(html || '', { USE_PROFILES: { html: true }, FORBID_TAGS: ['style', 'form', 'input'], FORBID_ATTR: ['style'] }) }
}

/** Internal links render with the router; anything else is a normal anchor. */
export const isInternal = (href) => typeof href === 'string' && href.startsWith('/') && !href.startsWith('//')

/** Only http(s), mailto, tel, relative and hash links are ever rendered. */
export function safeHref(href) {
  if (!href || typeof href !== 'string') return null
  if (isInternal(href) || href.startsWith('#')) return href
  return /^(https?:|mailto:|tel:)/i.test(href) ? href : null
}

export function telHref(phone) {
  return phone ? `tel:${phone.replace(/[^\d+]/g, '')}` : null
}

export function youtubeEmbed(url) {
  if (!url) return null
  const match = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{11})/)
  if (match) return `https://www.youtube-nocookie.com/embed/${match[1]}?autoplay=1&rel=0`
  const vimeo = url.match(/vimeo\.com\/(\d+)/)
  return vimeo ? `https://player.vimeo.com/video/${vimeo[1]}?autoplay=1` : null
}

export function stripHtml(html) {
  return (html || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()
}
