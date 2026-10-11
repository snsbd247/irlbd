import { ArrowLeft, ArrowRight } from 'lucide-react'
import { cn } from '../../lib/utils'

/** Builds a compact page list like 1 2 3 … 10 */
function pages(current, last) {
  if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1)
  const set = new Set([1, last, current - 1, current, current + 1])
  const list = [...set].filter((p) => p >= 1 && p <= last).sort((a, b) => a - b)
  const out = []
  list.forEach((p, i) => {
    if (i && p - list[i - 1] > 1) out.push('…' + p)
    out.push(p)
  })
  return out
}

export default function Pagination({ meta, onChange, className }) {
  if (!meta || meta.last_page <= 1) return null
  const { current_page: current, last_page: last } = meta
  const btn = 'inline-flex h-9 min-w-9 items-center justify-center rounded-md border px-2 text-sm font-medium transition-colors'

  return (
    <nav aria-label="Pagination" className={cn('flex flex-wrap items-center justify-center gap-2', className)}>
      <button type="button" className={cn(btn, 'border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40')} onClick={() => onChange(current - 1)} disabled={current <= 1} aria-label="Previous page">
        <ArrowLeft className="h-4 w-4" aria-hidden="true" />
      </button>
      {pages(current, last).map((p) =>
        typeof p === 'string' ? (
          <span key={p} className="px-1 text-slate-400" aria-hidden="true">…</span>
        ) : (
          <button
            key={p}
            type="button"
            onClick={() => onChange(p)}
            aria-current={p === current ? 'page' : undefined}
            className={cn(btn, p === current ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50')}
          >
            {p}
          </button>
        ),
      )}
      <button type="button" className={cn(btn, 'border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40')} onClick={() => onChange(current + 1)} disabled={current >= last} aria-label="Next page">
        <ArrowRight className="h-4 w-4" aria-hidden="true" />
      </button>
    </nav>
  )
}
