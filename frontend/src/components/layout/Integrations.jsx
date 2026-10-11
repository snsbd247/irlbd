import { useEffect } from 'react'
import { Helmet } from 'react-helmet-async'
import { useLocation } from 'react-router-dom'
import { useSettings } from '../../lib/queries'
import { safeHref } from '../../lib/utils'
import { BrandIcon, SOCIALS } from '../../lib/icons'
import { useT } from '../../lib/i18n'

const GA_ID = /^G-[A-Z0-9]{4,20}$/
const PIXEL_ID = /^[0-9]{6,20}$/

/** JSON-LD script. "<" is escaped so content can never close the script tag. */
export function JsonLd({ data }) {
  if (!data) return null
  return (
    <Helmet>
      <script type="application/ld+json">{JSON.stringify(data).replace(/</g, '\\u003c')}</script>
    </Helmet>
  )
}

function loadScript(id, src) {
  if (document.getElementById(id)) return
  const s = document.createElement('script')
  s.id = id
  s.async = true
  s.src = src
  document.head.appendChild(s)
}

/**
 * Loads Google Analytics 4 / Meta Pixel only when an admin has configured a valid ID,
 * and reports a page view on every client-side route change.
 */
export function Analytics() {
  const { data: s = {} } = useSettings()
  const { pathname, search } = useLocation()
  const ga = GA_ID.test(s.ga4_measurement_id || '') ? s.ga4_measurement_id : null
  const pixel = PIXEL_ID.test(s.facebook_pixel_id || '') ? s.facebook_pixel_id : null

  useEffect(() => {
    if (ga && !window.gtag) {
      window.dataLayer = window.dataLayer || []
      window.gtag = function gtag() {
        window.dataLayer.push(arguments) // eslint-disable-line prefer-rest-params
      }
      window.gtag('js', new Date())
      window.gtag('config', ga, { send_page_view: false })
      loadScript('ga4', `https://www.googletagmanager.com/gtag/js?id=${ga}`)
    }
    if (pixel && !window.fbq) {
      const fbq = function fbq() {
        fbq.callMethod ? fbq.callMethod.apply(fbq, arguments) : fbq.queue.push(arguments) // eslint-disable-line prefer-rest-params
      }
      fbq.queue = []
      fbq.loaded = true
      fbq.version = '2.0'
      window.fbq = window._fbq = fbq
      window.fbq('init', pixel)
      loadScript('fb-pixel', 'https://connect.facebook.net/en_US/fbevents.js')
    }
  }, [ga, pixel])

  useEffect(() => {
    if (ga && window.gtag) window.gtag('event', 'page_view', { page_path: pathname + search, page_location: window.location.href })
    if (pixel && window.fbq) window.fbq('track', 'PageView')
  }, [ga, pixel, pathname, search])

  return null
}

/** Organization structured data for search engines, built from General Settings. */
export function OrganizationJsonLd() {
  const { data: s } = useSettings()
  if (!s?.site_name) return null
  const origin = window.location.origin
  return (
    <JsonLd
      data={{
        '@context': 'https://schema.org',
        '@type': 'Organization',
        name: s.site_name,
        url: origin,
        logo: s.logo_header_url || undefined,
        email: s.contact_email || undefined,
        telephone: s.contact_phone || undefined,
        address: s.contact_address ? { '@type': 'PostalAddress', streetAddress: s.contact_address, addressCountry: 'BD' } : undefined,
        sameAs: SOCIALS.map(([key]) => safeHref(s[key])).filter((u) => u && u.startsWith('http')),
      }}
    />
  )
}

/** Floating chat buttons (bottom-left; back-to-top lives bottom-right): WhatsApp and Messenger. */
export function ChatButtons() {
  const t = useT()
  const { data: s = {} } = useSettings()
  const number = (s.whatsapp_number || '').replace(/[^\d]/g, '')
  const whatsapp = s.whatsapp_enabled && number.length >= 8
  const page = /^[A-Za-z0-9.]{3,80}$/.test(s.messenger_page || '') ? s.messenger_page : null
  const messenger = s.messenger_enabled && page
  if (!whatsapp && !messenger) return null
  const text = s.whatsapp_message ? `?text=${encodeURIComponent(s.whatsapp_message)}` : ''
  const base = 'flex h-14 w-14 items-center justify-center rounded-full text-white shadow-lg transition-transform hover:scale-105 focus-visible:outline-offset-4'
  return (
    <div className="fixed bottom-5 left-5 z-30 flex flex-col gap-3">
      {messenger && (
        <a href={`https://m.me/${page}`} target="_blank" rel="noopener noreferrer" aria-label={t('Chat with us on Messenger')} className={`${base} bg-gradient-to-br from-[#00B2FF] to-[#006AFF]`}>
          <BrandIcon name="messenger" className="h-7 w-7" />
        </a>
      )}
      {whatsapp && (
        <a href={`https://wa.me/${number}${text}`} target="_blank" rel="noopener noreferrer" aria-label={t('Chat with us on WhatsApp')} className={`${base} bg-[#25D366]`}>
          <BrandIcon name="whatsapp" className="h-7 w-7" />
        </a>
      )}
    </div>
  )
}
