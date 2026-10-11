import { useState } from 'react'
import { Card } from './ui'

const W = 320
const H = 120
const PAD_B = 18 // room for month labels
const GAP = 2 // surface gap between adjacent bars

const monthLabel = (ym, opts = { month: 'short' }) => new Date(`${ym}-01T00:00:00`).toLocaleDateString('en-GB', opts)

/**
 * One single-series bar chart per lead type (small multiples, one hue, no legend).
 * Bars: thin, 4px rounded top anchored to the baseline; hover/focus shows the value.
 */
function MonthlyBars({ title, months, values }) {
  const [active, setActive] = useState(null)
  const max = Math.max(1, ...values)
  const total = values.reduce((a, b) => a + b, 0)
  const slot = W / months.length
  const barW = Math.max(4, slot - GAP * 2 - 6)
  const plotH = H - PAD_B
  const shown = active ?? months.length - 1

  return (
    <div className="rounded-lg border border-slate-200 p-4">
      <div className="mb-2 flex items-baseline justify-between gap-3">
        <h3 className="text-sm font-semibold text-navy-900">{title}</h3>
        <p className="text-xs text-muted">{total} in 12 months</p>
      </div>
      <p className="mb-1 h-5 text-sm text-slate-700" aria-live="polite">
        <span className="font-semibold text-navy-900">{values[shown]}</span> · {monthLabel(months[shown], { month: 'long', year: 'numeric' })}
      </p>
      <svg viewBox={`0 0 ${W} ${H}`} className="h-32 w-full" role="img" aria-label={`${title}: ${total} in the last 12 months`} onMouseLeave={() => setActive(null)}>
        <line x1="0" x2={W} y1={plotH + 0.5} y2={plotH + 0.5} stroke="#cbd5e1" strokeWidth="1" />
        {values.map((v, i) => {
          const h = v ? Math.max(3, (v / max) * (plotH - 6)) : 0
          const x = i * slot + (slot - barW) / 2
          const y = plotH - h
          const r = Math.min(4, barW / 2, h)
          return (
            <g key={months[i]}>
              {h > 0 && (
                <path
                  d={`M${x},${plotH} V${y + r} Q${x},${y} ${x + r},${y} H${x + barW - r} Q${x + barW},${y} ${x + barW},${y + r} V${plotH} Z`}
                  fill="#1677e5"
                  opacity={active === null || active === i ? 1 : 0.45}
                />
              )}
              {/* Hit target: the whole column, larger than the mark. */}
              <rect
                x={i * slot} y="0" width={slot} height={H} fill="transparent"
                tabIndex={0} role="button" aria-label={`${monthLabel(months[i], { month: 'long', year: 'numeric' })}: ${v}`}
                onMouseEnter={() => setActive(i)} onFocus={() => setActive(i)} onBlur={() => setActive(null)}
                className="cursor-default outline-none focus-visible:fill-brand-600/10"
              />
              {(months.length - 1 - i) % 2 === 0 && (
                <text x={i * slot + slot / 2} y={H - 4} textAnchor="middle" fontSize="9" fill="#64748b">{monthLabel(months[i])}</text>
              )}
            </g>
          )
        })}
      </svg>
      <details className="mt-2 text-xs">
        <summary className="cursor-pointer text-muted">Show as table</summary>
        <table className="mt-2 w-full">
          <tbody>
            {months.map((m, i) => (
              <tr key={m} className="border-t border-slate-100">
                <td className="py-1 text-slate-600">{monthLabel(m, { month: 'long', year: 'numeric' })}</td>
                <td className="py-1 text-right font-medium text-navy-900">{values[i]}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </details>
    </div>
  )
}

const TITLES = { quotes: 'Quote requests', enquiries: 'Contact enquiries', applications: 'Job applications' }

export default function MonthlyReport({ report }) {
  if (!report || !Object.keys(report.series || {}).length) return null
  const top = report.top_products || []
  const topMax = Math.max(1, ...top.map((p) => p.count))

  return (
    <Card title="Last 12 months" className="mt-6">
      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        {Object.entries(report.series).map(([key, values]) => (
          <MonthlyBars key={key} title={TITLES[key] || key} months={report.months} values={values} />
        ))}
      </div>
      {top.length > 0 && (
        <div className="mt-6">
          <h3 className="mb-3 text-sm font-semibold text-navy-900">Most requested products (quotations, 12 months)</h3>
          <ul className="space-y-2">
            {top.map((p) => (
              <li key={p.name} className="grid grid-cols-[minmax(0,12rem)_1fr_2.5rem] items-center gap-3 text-sm">
                <span className="truncate text-slate-700" title={p.name}>{p.name}</span>
                <span className="h-2.5 rounded-r-[4px] bg-brand-600" style={{ width: `${(p.count / topMax) * 100}%` }} aria-hidden="true" />
                <span className="text-right font-medium text-navy-900">{p.count}</span>
              </li>
            ))}
          </ul>
        </div>
      )}
    </Card>
  )
}
