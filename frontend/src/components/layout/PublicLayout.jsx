import { useEffect } from 'react'
import { Outlet, useLocation } from 'react-router-dom'
import { useSettings } from '../../lib/queries'
import Header from './Header'
import Footer from './Footer'
import { Analytics, OrganizationJsonLd, WhatsAppButton } from './Integrations'

/** Keeps the favicon in sync with the one configured in General Settings. */
function useFavicon(url) {
  useEffect(() => {
    if (!url) return
    let link = document.querySelector("link[rel='icon']")
    if (!link) {
      link = document.createElement('link')
      link.rel = 'icon'
      document.head.appendChild(link)
    }
    link.href = url
  }, [url])
}

function ScrollToTop() {
  const { pathname } = useLocation()
  useEffect(() => {
    window.scrollTo(0, 0)
  }, [pathname])
  return null
}

export default function PublicLayout() {
  const { data: s } = useSettings()
  useFavicon(s?.favicon_url)

  return (
    <div className="flex min-h-screen flex-col">
      <a href="#main" className="sr-only z-50 rounded bg-brand-600 px-4 py-2 text-white focus:not-sr-only focus:fixed focus:top-2 focus:left-2">
        Skip to content
      </a>
      <ScrollToTop />
      <Header />
      <main id="main" className="flex-1">
        <Outlet />
      </main>
      <Footer />
      <WhatsAppButton />
      <Analytics />
      <OrganizationJsonLd />
    </div>
  )
}
