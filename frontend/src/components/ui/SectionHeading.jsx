import { cn } from '../../lib/utils'

export default function SectionHeading({ eyebrow, title, subtitle, align = 'left', className, as: Tag = 'h2' }) {
  if (!eyebrow && !title && !subtitle) return null
  const center = align === 'center'
  return (
    <div className={cn(center ? 'mx-auto max-w-3xl text-center' : 'max-w-2xl', className)}>
      {eyebrow && <p className={cn('eyebrow', center && 'eyebrow-center')}>{eyebrow}</p>}
      {title && <Tag className="heading-lg mt-2">{title}</Tag>}
      {subtitle && <p className="mt-3 text-muted">{subtitle}</p>}
    </div>
  )
}
