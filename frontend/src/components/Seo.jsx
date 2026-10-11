import { Helmet } from 'react-helmet-async'
import { useLocation } from 'react-router-dom'
import { useSettings } from '../lib/queries'
import { useLang } from '../lib/i18n'

/**
 * Per-page metadata with fallbacks to the SEO defaults from General Settings.
 */
export default function Seo({ title, description, keywords, image, type = 'website', noindex = false }) {
  const { data: s = {} } = useSettings()
  const { pathname } = useLocation()
  const { lang } = useLang()

  const siteName = s.site_name || 'IR Limited'
  const sep = s.seo_title_separator || '|'
  const fullTitle = title ? (title.includes(siteName) ? title : `${title} ${sep} ${siteName}`) : s.seo_title || siteName
  const desc = description || s.seo_description || ''
  const ogImage = image || s.seo_og_image_url
  const base = typeof window !== 'undefined' ? `${window.location.origin}${pathname}` : pathname
  // Bengali versions live at the same path with ?lang=bn (see LanguageProvider).
  const bnUrl = `${base}?lang=bn`
  const url = lang === 'bn' ? bnUrl : base

  return (
    <Helmet>
      <title>{fullTitle}</title>
      {desc && <meta name="description" content={desc} />}
      {(keywords || s.seo_keywords) && <meta name="keywords" content={keywords || s.seo_keywords} />}
      <link rel="canonical" href={url} />
      {!noindex && <link rel="alternate" hrefLang="en" href={base} />}
      {!noindex && <link rel="alternate" hrefLang="bn" href={bnUrl} />}
      {!noindex && <link rel="alternate" hrefLang="x-default" href={base} />}
      <meta property="og:locale" content={lang === 'bn' ? 'bn_BD' : 'en_US'} />
      {noindex && <meta name="robots" content="noindex, nofollow" />}
      <meta property="og:site_name" content={siteName} />
      <meta property="og:type" content={type} />
      <meta property="og:title" content={fullTitle} />
      {desc && <meta property="og:description" content={desc} />}
      <meta property="og:url" content={url} />
      {ogImage && <meta property="og:image" content={ogImage} />}
      <meta name="twitter:card" content={ogImage ? 'summary_large_image' : 'summary'} />
      {s.google_site_verification && <meta name="google-site-verification" content={s.google_site_verification} />}
    </Helmet>
  )
}
