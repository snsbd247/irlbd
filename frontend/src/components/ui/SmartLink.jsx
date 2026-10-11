import { Link } from 'react-router-dom'
import { isInternal, safeHref } from '../../lib/utils'

/** Router link for internal paths, safe anchor for external ones, plain span if the URL is unsafe. */
export default function SmartLink({ href, newTab = false, children, ...props }) {
  const url = safeHref(href)
  if (!url) return <span {...props}>{children}</span>
  if (isInternal(url) && !newTab) {
    return (
      <Link to={url} {...props}>
        {children}
      </Link>
    )
  }
  const external = /^https?:/i.test(url)
  return (
    <a href={url} {...(newTab || external ? { target: '_blank', rel: 'noopener noreferrer' } : {})} {...props}>
      {children}
    </a>
  )
}
