import { useEffect, useRef, useState } from 'react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { ArrowRight, ChevronDown, Mail, Menu, Phone, Search, X } from 'lucide-react'
import { useNavigation, useSettings } from '../../lib/queries'
import { BrandIcon, SOCIALS } from '../../lib/icons'
import { cn, safeHref, telHref } from '../../lib/utils'
import SmartLink from '../ui/SmartLink'

export function Logo({ settings, light = false, className = 'h-12 sm:h-14' }) {
  const src = light ? settings?.logo_light_url || settings?.logo_footer_url : settings?.logo_header_url || settings?.logo_dark_url
  if (src) return <img src={src} alt={settings?.site_name || 'Home'} className={cn('w-auto', className)} />
  return <span className={cn('text-xl font-bold', light ? 'text-white' : 'text-navy-900')}>{settings?.site_name || 'IR Limited'}</span>
}

function TopBar({ s }) {
  if (!s.topbar_enabled) return null
  return (
    <div className="hidden bg-navy-900 text-xs text-white/90 md:block">
      <div className="container-x flex h-9 items-center justify-between gap-4">
        <p className="truncate">{s.topbar_text}</p>
        <div className="flex items-center gap-5">
          {s.contact_phone && (
            <a href={telHref(s.contact_phone)} className="flex items-center gap-1.5 hover:text-white">
              <Phone className="h-3.5 w-3.5 text-brand-500" aria-hidden="true" /> {s.contact_phone}
            </a>
          )}
          {s.contact_email && (
            <a href={`mailto:${s.contact_email}`} className="flex items-center gap-1.5 hover:text-white">
              <Mail className="h-3.5 w-3.5 text-brand-500" aria-hidden="true" /> {s.contact_email}
            </a>
          )}
          <div className="flex items-center gap-3">
            {SOCIALS.filter(([key]) => safeHref(s[key])).map(([key, icon, label]) => (
              <a key={key} href={safeHref(s[key])} target="_blank" rel="noopener noreferrer" aria-label={label} className="hover:text-brand-500">
                <BrandIcon name={icon} className="h-3.5 w-3.5" />
              </a>
            ))}
          </div>
        </div>
      </div>
    </div>
  )
}

function isActive(item, pathname) {
  const match = (href) => href && (href === '/' ? pathname === '/' : pathname === href || pathname.startsWith(href + '/'))
  return match(item.href) || (item.children || []).some((c) => match(c.href))
}

function DesktopNav({ items, pathname }) {
  const [open, setOpen] = useState(null)

  return (
    <nav aria-label="Main" className="hidden lg:block">
      <ul className="flex items-center gap-1">
        {items.map((item) => {
          const active = isActive(item, pathname)
          const hasChildren = item.children?.length > 0
          return (
            <li
              key={item.id}
              className="relative"
              onMouseEnter={() => hasChildren && setOpen(item.id)}
              onMouseLeave={() => setOpen(null)}
              onBlur={(e) => !e.currentTarget.contains(e.relatedTarget) && setOpen(null)}
            >
              <div className="flex items-center">
                <SmartLink
                  href={item.href}
                  newTab={item.open_in_new_tab}
                  className={cn(
                    'relative px-3 py-7 text-[13px] font-semibold tracking-wide uppercase transition-colors hover:text-brand-600',
                    active ? 'text-brand-600 after:absolute after:inset-x-3 after:bottom-5 after:h-0.5 after:bg-brand-600' : 'text-navy-900',
                  )}
                >
                  {item.label}
                </SmartLink>
                {hasChildren && (
                  <button
                    type="button"
                    className="-ml-2 p-1 text-navy-900 hover:text-brand-600"
                    aria-expanded={open === item.id}
                    aria-label={`Show ${item.label} submenu`}
                    onClick={() => setOpen(open === item.id ? null : item.id)}
                  >
                    <ChevronDown className="h-3.5 w-3.5" aria-hidden="true" />
                  </button>
                )}
              </div>
              {hasChildren && open === item.id && (
                <ul className="absolute top-full left-0 z-40 min-w-56 overflow-hidden rounded-b-md border-t-2 border-brand-600 bg-white py-2 shadow-xl">
                  {item.children.map((child) => (
                    <li key={child.id}>
                      <SmartLink
                        href={child.href}
                        newTab={child.open_in_new_tab}
                        onClick={() => setOpen(null)}
                        className={cn(
                          'block px-5 py-2.5 text-sm transition-colors hover:bg-brand-50 hover:text-brand-600',
                          pathname === child.href ? 'bg-brand-50 text-brand-600' : 'text-slate-600',
                        )}
                      >
                        {child.label}
                      </SmartLink>
                    </li>
                  ))}
                </ul>
              )}
            </li>
          )
        })}
      </ul>
    </nav>
  )
}

function MobileNav({ items, open, onClose, s, pathname }) {
  const [expanded, setExpanded] = useState(null)
  if (!open) return null
  return (
    <div className="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Menu">
      <button type="button" className="absolute inset-0 bg-navy-950/60" onClick={onClose} aria-label="Close menu" />
      <div className="absolute inset-y-0 right-0 flex w-[85%] max-w-sm flex-col overflow-y-auto bg-white shadow-2xl">
        <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
          <Logo settings={s} className="h-11" />
          <button type="button" onClick={onClose} className="rounded-md p-2 text-navy-900 hover:bg-slate-100" aria-label="Close menu">
            <X className="h-5 w-5" aria-hidden="true" />
          </button>
        </div>
        <ul className="flex-1 px-3 py-3">
          {items.map((item) => (
            <li key={item.id} className="border-b border-slate-100 last:border-0">
              <div className="flex items-center justify-between">
                <SmartLink
                  href={item.href}
                  newTab={item.open_in_new_tab}
                  onClick={onClose}
                  className={cn('block flex-1 px-2 py-3 text-sm font-semibold uppercase', isActive(item, pathname) ? 'text-brand-600' : 'text-navy-900')}
                >
                  {item.label}
                </SmartLink>
                {item.children?.length > 0 && (
                  <button
                    type="button"
                    className="p-3 text-navy-900"
                    aria-expanded={expanded === item.id}
                    aria-label={`Toggle ${item.label} submenu`}
                    onClick={() => setExpanded(expanded === item.id ? null : item.id)}
                  >
                    <ChevronDown className={cn('h-4 w-4 transition-transform', expanded === item.id && 'rotate-180')} aria-hidden="true" />
                  </button>
                )}
              </div>
              {expanded === item.id && (
                <ul className="mb-2 ml-3 border-l-2 border-brand-100">
                  {item.children.map((child) => (
                    <li key={child.id}>
                      <SmartLink href={child.href} newTab={child.open_in_new_tab} onClick={onClose} className="block px-4 py-2 text-sm text-slate-600 hover:text-brand-600">
                        {child.label}
                      </SmartLink>
                    </li>
                  ))}
                </ul>
              )}
            </li>
          ))}
        </ul>
        <div className="space-y-3 border-t border-slate-100 p-5 text-sm">
          {s.contact_phone && (
            <a href={telHref(s.contact_phone)} className="flex items-center gap-2 text-navy-900">
              <Phone className="h-4 w-4 text-brand-600" aria-hidden="true" /> {s.contact_phone}
            </a>
          )}
          {s.contact_email && (
            <a href={`mailto:${s.contact_email}`} className="flex items-center gap-2 text-navy-900">
              <Mail className="h-4 w-4 text-brand-600" aria-hidden="true" /> {s.contact_email}
            </a>
          )}
          {s.header_cta_label && (
            <SmartLink href={s.header_cta_url || '/contact'} onClick={onClose} className="btn-primary w-full">
              {s.header_cta_label} <ArrowRight className="h-4 w-4" aria-hidden="true" />
            </SmartLink>
          )}
        </div>
      </div>
    </div>
  )
}

function SearchOverlay({ open, onClose }) {
  const [q, setQ] = useState('')
  const navigate = useNavigate()
  const input = useRef(null)

  useEffect(() => {
    if (!open) return
    input.current?.focus()
    const onKey = (e) => e.key === 'Escape' && onClose()
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [open, onClose])

  if (!open) return null
  return (
    <div className="fixed inset-0 z-50 flex items-start justify-center bg-navy-950/70 px-4 pt-28" role="dialog" aria-modal="true" aria-label="Search">
      <button type="button" className="absolute inset-0" onClick={onClose} aria-label="Close search" />
      <form
        className="relative flex w-full max-w-2xl overflow-hidden rounded-lg bg-white shadow-2xl"
        onSubmit={(e) => {
          e.preventDefault()
          if (q.trim().length >= 2) {
            navigate(`/search?q=${encodeURIComponent(q.trim())}`)
            onClose()
          }
        }}
      >
        <label htmlFor="site-search" className="sr-only">Search the website</label>
        <input
          ref={input}
          id="site-search"
          type="search"
          value={q}
          onChange={(e) => setQ(e.target.value)}
          placeholder="Search products, news and pages…"
          minLength={2}
          className="flex-1 px-5 py-4 text-base outline-none"
        />
        <button type="submit" className="bg-brand-600 px-5 text-white hover:bg-brand-700" aria-label="Search">
          <Search className="h-5 w-5" aria-hidden="true" />
        </button>
      </form>
    </div>
  )
}

export default function Header() {
  const { data: s = {} } = useSettings()
  const { data: nav } = useNavigation()
  const { pathname } = useLocation()
  const [mobileOpen, setMobileOpen] = useState(false)
  const [searchOpen, setSearchOpen] = useState(false)
  const items = nav?.header || []

  useEffect(() => {
    document.body.style.overflow = mobileOpen ? 'hidden' : ''
    return () => {
      document.body.style.overflow = ''
    }
  }, [mobileOpen])

  return (
    <>
      <TopBar s={s} />
      <header className="sticky top-0 z-40 border-b border-slate-100 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/90">
        <div className="container-x flex h-[76px] items-center justify-between gap-4">
          <Link to="/" className="shrink-0" aria-label={`${s.site_name || 'IR Limited'} home`}>
            <Logo settings={s} />
          </Link>
          <DesktopNav items={items} pathname={pathname} />
          <div className="flex items-center gap-2 sm:gap-3">
            <button type="button" onClick={() => setSearchOpen(true)} className="icon-circle h-10 w-10 bg-slate-100 text-navy-900 hover:bg-brand-50 hover:text-brand-600" aria-label="Search">
              <Search className="h-4 w-4" aria-hidden="true" />
            </button>
            {s.header_cta_label && (
              <SmartLink href={s.header_cta_url || '/contact'} className="btn-primary hidden sm:inline-flex">
                {s.header_cta_label} <ArrowRight className="h-4 w-4" aria-hidden="true" />
              </SmartLink>
            )}
            <button type="button" onClick={() => setMobileOpen(true)} className="rounded-md p-2 text-navy-900 hover:bg-slate-100 lg:hidden" aria-label="Open menu" aria-expanded={mobileOpen}>
              <Menu className="h-6 w-6" aria-hidden="true" />
            </button>
          </div>
        </div>
      </header>
      <MobileNav items={items} open={mobileOpen} onClose={() => setMobileOpen(false)} s={s} pathname={pathname} />
      <SearchOverlay open={searchOpen} onClose={() => setSearchOpen(false)} />
    </>
  )
}
