import { CircleAlert, Inbox, LoaderCircle, RefreshCw } from 'lucide-react'
import { cn } from '../../lib/utils'

export function Spinner({ className = 'h-6 w-6', label = 'Loading' }) {
  return (
    <span role="status" className="inline-flex items-center">
      <LoaderCircle className={cn('animate-spin text-brand-600', className)} aria-hidden="true" />
      <span className="sr-only">{label}</span>
    </span>
  )
}

export function PageLoader() {
  return (
    <div className="flex min-h-[50vh] items-center justify-center">
      <Spinner className="h-10 w-10" label="Loading page" />
    </div>
  )
}

export function ErrorState({ title = 'Something went wrong', message = 'We could not load this content. Please try again.', onRetry, className }) {
  return (
    <div role="alert" className={cn('flex flex-col items-center justify-center gap-3 rounded-xl border border-red-100 bg-red-50/60 px-6 py-10 text-center', className)}>
      <CircleAlert className="h-8 w-8 text-red-500" aria-hidden="true" />
      <div>
        <p className="font-semibold text-navy-900">{title}</p>
        <p className="mt-1 text-sm text-muted">{message}</p>
      </div>
      {onRetry && (
        <button type="button" onClick={onRetry} className="btn-outline mt-1">
          <RefreshCw className="h-4 w-4" aria-hidden="true" /> Try again
        </button>
      )}
    </div>
  )
}

export function EmptyState({ title = 'Nothing here yet', message, icon: IconComponent = Inbox, children, className }) {
  return (
    <div className={cn('flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-slate-200 px-6 py-12 text-center', className)}>
      <IconComponent className="h-9 w-9 text-slate-300" aria-hidden="true" />
      <p className="font-semibold text-navy-900">{title}</p>
      {message && <p className="max-w-md text-sm text-muted">{message}</p>}
      {children}
    </div>
  )
}

export function Skeleton({ className }) {
  return <div className={cn('animate-pulse rounded-md bg-slate-200/70', className)} aria-hidden="true" />
}

export function CardGridSkeleton({ count = 6, className = 'grid gap-6 sm:grid-cols-2 lg:grid-cols-3' }) {
  return (
    <div className={className} role="status" aria-label="Loading">
      {Array.from({ length: count }).map((_, i) => (
        <div key={i} className="card overflow-hidden">
          <Skeleton className="h-48 rounded-none" />
          <div className="space-y-3 p-5">
            <Skeleton className="h-4 w-1/3" />
            <Skeleton className="h-5 w-3/4" />
            <Skeleton className="h-4 w-full" />
          </div>
        </div>
      ))}
    </div>
  )
}
