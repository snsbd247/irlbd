import { useState } from 'react'
import { CircleCheck } from 'lucide-react'
import { api, errorMessage, fieldErrors } from '../../lib/api'
import { cn } from '../../lib/utils'
import { Spinner } from '../ui/States'

export default function NewsletterForm({ dark = false, compact = false, idPrefix = 'newsletter' }) {
  const [email, setEmail] = useState('')
  const [website, setWebsite] = useState('') // honeypot
  const [state, setState] = useState({ status: 'idle', message: '' })

  async function submit(e) {
    e.preventDefault()
    setState({ status: 'loading', message: '' })
    try {
      const { data } = await api.post('/newsletter/subscribe', { email, website })
      setState({ status: 'success', message: data.message })
      setEmail('')
    } catch (error) {
      setState({ status: 'error', message: fieldErrors(error).email || errorMessage(error) })
    }
  }

  const inputId = `${idPrefix}-email`
  return (
    <form onSubmit={submit} noValidate className="w-full">
      <div className={cn('flex overflow-hidden rounded-md', compact ? 'flex-col gap-2 sm:flex-row sm:gap-0' : 'flex-row')}>
        <label htmlFor={inputId} className="sr-only">Email address</label>
        <input
          id={inputId}
          type="email"
          required
          autoComplete="email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          placeholder="Enter your email address"
          aria-invalid={state.status === 'error'}
          aria-describedby={`${inputId}-msg`}
          className={cn(
            'min-w-0 flex-1 px-4 py-3 text-sm outline-none',
            dark ? 'bg-white text-ink' : 'border border-slate-300 bg-white focus:border-brand-600',
            compact ? 'rounded-md sm:rounded-r-none' : 'rounded-l-md',
          )}
        />
        <input type="text" tabIndex={-1} autoComplete="off" value={website} onChange={(e) => setWebsite(e.target.value)} className="hidden" aria-hidden="true" />
        <button type="submit" disabled={state.status === 'loading'} className={cn('btn-primary rounded-none px-6', compact ? 'rounded-md sm:rounded-l-none' : 'rounded-r-md')}>
          {state.status === 'loading' ? <Spinner className="h-4 w-4 text-white" label="Subscribing" /> : 'Subscribe'}
        </button>
      </div>
      <p id={`${inputId}-msg`} role="status" className={cn('mt-2 min-h-5 text-xs', state.status === 'error' ? 'text-red-400' : dark ? 'text-emerald-300' : 'text-emerald-600')}>
        {state.status === 'success' && <CircleCheck className="mr-1 inline h-3.5 w-3.5" aria-hidden="true" />}
        {state.message}
      </p>
    </form>
  )
}
