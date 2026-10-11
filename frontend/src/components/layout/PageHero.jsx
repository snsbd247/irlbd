import { Fragment } from 'react'
import { Link } from 'react-router-dom'
import { ChevronRight } from 'lucide-react'

/**
 * Dark navy banner with breadcrumbs used on every inner page.
 * crumbs: [{ label, href? }] — the last crumb is the current page.
 */
export default function PageHero({ title, subtitle, image, crumbs = [], children }) {
  return (
    <section className="relative overflow-hidden bg-navy-900 text-white">
      {image && <img src={image} alt="" aria-hidden="true" className="absolute inset-0 h-full w-full object-cover opacity-60" />}
      <div className="absolute inset-0 bg-gradient-to-r from-navy-950 via-navy-900/85 to-navy-900/20" aria-hidden="true" />
      <div className="container-x relative py-14 sm:py-16 lg:py-20">
        <nav aria-label="Breadcrumb">
          <ol className="flex flex-wrap items-center gap-1.5 text-sm">
            <li>
              <Link to="/" className="text-white/90 hover:text-white">Home</Link>
            </li>
            {crumbs.map((c, i) => (
              <Fragment key={i}>
                <li aria-hidden="true">
                  <ChevronRight className="h-3.5 w-3.5 text-white/60" />
                </li>
                <li>
                  {c.href && i < crumbs.length - 1 ? (
                    <Link to={c.href} className="text-brand-500 hover:text-white">{c.label}</Link>
                  ) : (
                    <span aria-current={i === crumbs.length - 1 ? 'page' : undefined} className="text-white/90">{c.label}</span>
                  )}
                </li>
              </Fragment>
            ))}
          </ol>
        </nav>
        <span className="mt-3 block h-0.5 w-10 bg-brand-500" aria-hidden="true" />
        <h1 className="mt-3 max-w-3xl text-3xl font-bold sm:text-4xl lg:text-5xl">{title}</h1>
        {subtitle && <p className="mt-3 max-w-2xl text-base text-white/85 sm:text-lg">{subtitle}</p>}
        {children}
      </div>
    </section>
  )
}
