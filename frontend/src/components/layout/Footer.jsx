import { useEffect, useState } from 'react'
import { ArrowUp, Mail, MapPin, Phone } from 'lucide-react'
import { useNavigation, useSettings } from '../../lib/queries'
import { BrandIcon, SOCIALS } from '../../lib/icons'
import { safeHref, telHref } from '../../lib/utils'
import SmartLink from '../ui/SmartLink'
import NewsletterForm from './NewsletterForm'
import BrochureButton from '../ui/BrochureButton'
import { Logo } from './Header'
import { useT } from '../../lib/i18n'

function LinkColumn({ title, items }) {
  if (!items?.length) return null
  return (
    <div>
      <h2 className="mb-4 text-sm font-semibold text-white">{title}</h2>
      <ul className="space-y-2 text-sm">
        {items.map((item) => (
          <li key={item.id}>
            <SmartLink href={item.href} newTab={item.open_in_new_tab} className="text-white/70 transition-colors hover:text-brand-500">
              {item.label}
            </SmartLink>
          </li>
        ))}
      </ul>
    </div>
  )
}

function BackToTop() {
  const t = useT()
  const [visible, setVisible] = useState(false)
  useEffect(() => {
    const onScroll = () => setVisible(window.scrollY > 600)
    window.addEventListener('scroll', onScroll, { passive: true })
    return () => window.removeEventListener('scroll', onScroll)
  }, [])
  if (!visible) return null
  return (
    <button
      type="button"
      onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}
      className="fixed right-5 bottom-5 z-30 flex h-11 w-11 items-center justify-center rounded-md bg-brand-600 text-white shadow-lg hover:bg-brand-700"
      aria-label={t('Back to top')}
    >
      <ArrowUp className="h-5 w-5" aria-hidden="true" />
    </button>
  )
}

export default function Footer() {
  const t = useT()
  const { data: s = {} } = useSettings()
  const { data: nav } = useNavigation()
  const copyright = (s.copyright_text || `© {year} ${s.site_name || 'IR Limited'}. All Rights Reserved.`).replace('{year}', new Date().getFullYear())

  return (
    <footer className="bg-navy-950 text-white">
      <div className="container-x grid gap-10 py-12 md:grid-cols-2 lg:grid-cols-12">
        <div className="lg:col-span-4 lg:border-r lg:border-white/10 lg:pr-8">
          <Logo settings={s} light className="h-14" />
          {s.footer_about && <p className="mt-4 max-w-sm text-sm leading-relaxed text-white/70">{s.footer_about}</p>}
          <BrochureButton className="btn mt-5 border border-white/30 px-4 py-2 text-white hover:border-brand-500 hover:bg-brand-600" />
          <div className="mt-5 flex gap-3">
            {SOCIALS.filter(([key]) => safeHref(s[key])).map(([key, icon, label]) => (
              <a
                key={key}
                href={safeHref(s[key])}
                target="_blank"
                rel="noopener noreferrer"
                aria-label={label}
                className="flex h-9 w-9 items-center justify-center rounded-full border border-white/30 text-white transition-colors hover:border-brand-500 hover:bg-brand-600"
              >
                <BrandIcon name={icon} />
              </a>
            ))}
          </div>
        </div>
        <div className="grid grid-cols-2 gap-8 lg:col-span-4 lg:border-r lg:border-white/10 lg:px-4">
          <LinkColumn title={t('Quick Links')} items={nav?.footer_quick} />
          <LinkColumn title={t('Our Products')} items={nav?.footer_products} />
        </div>
        <div className="md:col-span-2 lg:col-span-4">
          <h2 className="mb-2 text-sm font-semibold">{s.newsletter_title || t('Newsletter')}</h2>
          {s.newsletter_text && <p className="mb-4 text-sm text-white/70">{s.newsletter_text}</p>}
          <NewsletterForm dark idPrefix="footer-newsletter" />
        </div>
      </div>
      <div className="border-t border-white/10">
        <div className="container-x flex flex-col gap-4 py-5 text-xs text-white/70 lg:flex-row lg:items-center lg:justify-between">
          <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:gap-8">
            {s.contact_address && (
              <span className="flex items-start gap-2">
                <MapPin className="h-4 w-4 shrink-0 text-brand-500" aria-hidden="true" /> {s.contact_address}
              </span>
            )}
            {s.contact_phone && (
              <a href={telHref(s.contact_phone)} className="flex items-center gap-2 hover:text-white">
                <Phone className="h-4 w-4 text-brand-500" aria-hidden="true" /> {s.contact_phone}
              </a>
            )}
            {s.contact_email && (
              <a href={`mailto:${s.contact_email}`} className="flex items-center gap-2 hover:text-white">
                <Mail className="h-4 w-4 text-brand-500" aria-hidden="true" /> {s.contact_email}
              </a>
            )}
          </div>
          <p>{copyright}</p>
        </div>
      </div>
      <BackToTop />
    </footer>
  )
}
