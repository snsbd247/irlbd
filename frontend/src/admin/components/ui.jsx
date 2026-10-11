import { createContext, useCallback, useContext, useEffect, useRef, useState } from 'react'
import { CircleAlert, CircleCheck, X } from 'lucide-react'
import { cn } from '../../lib/utils'
import { Spinner } from '../../components/ui/States'

/* ----------------------------------------------------------------- Toasts */

const ToastContext = createContext(() => {})

export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([])
  const push = useCallback((message, type = 'success') => {
    const id = Math.random().toString(36).slice(2)
    setToasts((t) => [...t, { id, message, type }])
    setTimeout(() => setToasts((t) => t.filter((x) => x.id !== id)), type === 'error' ? 7000 : 4000)
  }, [])

  return (
    <ToastContext.Provider value={push}>
      {children}
      <div className="fixed right-4 bottom-4 z-[60] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-2" role="status" aria-live="polite">
        {toasts.map((t) => (
          <div key={t.id} className={cn('flex items-start gap-3 rounded-lg px-4 py-3 text-sm shadow-lg', t.type === 'error' ? 'bg-red-600 text-white' : 'bg-navy-900 text-white')}>
            {t.type === 'error' ? <CircleAlert className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" /> : <CircleCheck className="mt-0.5 h-4 w-4 shrink-0 text-emerald-400" aria-hidden="true" />}
            <span className="flex-1">{t.message}</span>
            <button type="button" onClick={() => setToasts((all) => all.filter((x) => x.id !== t.id))} aria-label="Dismiss">
              <X className="h-4 w-4 opacity-70" aria-hidden="true" />
            </button>
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  )
}

export const useToast = () => useContext(ToastContext)

/* ------------------------------------------------------------------ Modal */

export function Modal({ open, onClose, title, children, footer, size = 'lg' }) {
  const panel = useRef(null)
  useEffect(() => {
    if (!open) return
    const previous = document.activeElement
    panel.current?.focus()
    const onKey = (e) => e.key === 'Escape' && onClose()
    window.addEventListener('keydown', onKey)
    document.body.style.overflow = 'hidden'
    return () => {
      window.removeEventListener('keydown', onKey)
      document.body.style.overflow = ''
      previous?.focus?.()
    }
  }, [open, onClose])

  if (!open) return null
  const widths = { sm: 'max-w-md', md: 'max-w-xl', lg: 'max-w-3xl', xl: 'max-w-5xl' }
  return (
    <div className="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-navy-950/60 p-4 sm:p-8" role="dialog" aria-modal="true" aria-labelledby="modal-title">
      <button type="button" className="fixed inset-0 cursor-default" onClick={onClose} aria-label="Close dialog" tabIndex={-1} />
      <div ref={panel} tabIndex={-1} className={cn('relative w-full rounded-xl bg-white shadow-2xl outline-none', widths[size])}>
        <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
          <h2 id="modal-title" className="text-lg font-semibold text-navy-900">{title}</h2>
          <button type="button" onClick={onClose} className="rounded-md p-1.5 text-slate-500 hover:bg-slate-100" aria-label="Close">
            <X className="h-5 w-5" aria-hidden="true" />
          </button>
        </div>
        <div className="px-5 py-5">{children}</div>
        {footer && <div className="flex justify-end gap-2 border-t border-slate-100 px-5 py-4">{footer}</div>}
      </div>
    </div>
  )
}

/* --------------------------------------------------------- Confirm dialog */

const ConfirmContext = createContext(() => Promise.resolve(false))

export function ConfirmProvider({ children }) {
  const [state, setState] = useState(null)
  const confirm = useCallback(
    (message, { title = 'Are you sure?', confirmLabel = 'Delete', danger = true } = {}) =>
      new Promise((resolve) => setState({ message, title, confirmLabel, danger, resolve })),
    [],
  )
  const close = (result) => {
    state?.resolve(result)
    setState(null)
  }
  return (
    <ConfirmContext.Provider value={confirm}>
      {children}
      <Modal
        open={!!state}
        onClose={() => close(false)}
        title={state?.title}
        size="sm"
        footer={
          <>
            <button type="button" className="btn-outline" onClick={() => close(false)}>Cancel</button>
            <button type="button" className={cn('btn', state?.danger ? 'bg-red-600 text-white hover:bg-red-700' : 'btn-primary')} onClick={() => close(true)}>
              {state?.confirmLabel}
            </button>
          </>
        }
      >
        <p className="text-sm text-slate-600">{state?.message}</p>
      </Modal>
    </ConfirmContext.Provider>
  )
}

export const useConfirm = () => useContext(ConfirmContext)

/* --------------------------------------------------------------- Bits */

export function PageHeader({ title, description, actions }) {
  return (
    <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 className="text-2xl font-bold text-navy-900">{title}</h1>
        {description && <p className="mt-1 text-sm text-muted">{description}</p>}
      </div>
      {actions && <div className="flex flex-wrap gap-2">{actions}</div>}
    </div>
  )
}

export function Badge({ children, tone = 'slate' }) {
  const tones = {
    slate: 'bg-slate-100 text-slate-700',
    green: 'bg-emerald-50 text-emerald-700',
    blue: 'bg-brand-50 text-brand-700',
    amber: 'bg-amber-50 text-amber-700',
    red: 'bg-red-50 text-red-700',
  }
  return <span className={cn('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', tones[tone])}>{children}</span>
}

export function Toggle({ checked, onChange, label, id, disabled }) {
  return (
    <label htmlFor={id} className={cn('inline-flex cursor-pointer items-center gap-3', disabled && 'cursor-not-allowed opacity-60')}>
      <span className="relative inline-flex">
        <input id={id} type="checkbox" className="peer sr-only" checked={!!checked} onChange={(e) => onChange(e.target.checked)} disabled={disabled} />
        <span className="h-6 w-11 rounded-full bg-slate-300 transition-colors peer-checked:bg-brand-600 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600 peer-focus-visible:ring-offset-2" />
        <span className="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5" />
      </span>
      {label && <span className="text-sm text-ink">{label}</span>}
    </label>
  )
}

export function SaveButton({ saving, children = 'Save changes', ...props }) {
  return (
    <button type="submit" className="btn-primary" disabled={saving} {...props}>
      {saving && <Spinner className="h-4 w-4 text-white" label="Saving" />}
      {children}
    </button>
  )
}

export function Card({ title, actions, children, className }) {
  return (
    <section className={cn('rounded-xl border border-slate-200 bg-white', className)}>
      {(title || actions) && (
        <div className="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3.5">
          {title && <h2 className="font-semibold text-navy-900">{title}</h2>}
          {actions}
        </div>
      )}
      <div className="p-5">{children}</div>
    </section>
  )
}

export function statusTone(status) {
  return { published: 'green', subscribed: 'green', new: 'blue', read: 'slate', replied: 'green', archived: 'slate', draft: 'amber', unsubscribed: 'slate' }[status] || 'slate'
}
