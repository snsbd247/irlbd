import { useEffect, useState } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { Download, Eye, Mail, Phone, Search, Trash2 } from 'lucide-react'
import { api, errorMessage } from '../../lib/api'
import { cn, formatDate, telHref } from '../../lib/utils'
import { EmptyState, ErrorState, Spinner } from '../../components/ui/States'
import Pagination from '../../components/ui/Pagination'
import { Badge, Card, Modal, PageHeader, statusTone, useConfirm, useToast } from '../components/ui'
import { useAuth } from '../auth'
import { MediaDetails, MediaGrid, UploadButton } from '../components/Media'
import { NotAllowed } from './Misc'

async function downloadCsv(endpoint, filename, toast) {
  try {
    const res = await api.get(endpoint, { responseType: 'blob' })
    const url = URL.createObjectURL(res.data)
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    a.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    toast(errorMessage(e, 'Export failed.'), 'error')
  }
}

function useList(key, endpoint, params, enabled) {
  return useQuery({
    queryKey: ['admin', key, params],
    queryFn: () => api.get(endpoint, { params }).then((r) => r.data),
    placeholderData: (prev) => prev,
    enabled,
  })
}

function SearchBar({ value, onChange, statuses, status, onStatus, placeholder }) {
  return (
    <div className="mb-4 flex flex-col gap-2 sm:flex-row">
      <div className="relative flex-1">
        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
        <input type="search" aria-label="Search" className="input pl-9" placeholder={placeholder} value={value} onChange={(e) => onChange(e.target.value)} />
      </div>
      <select aria-label="Status" className="input sm:w-44" value={status} onChange={(e) => onStatus(e.target.value)}>
        <option value="">All statuses</option>
        {statuses.map((s) => <option key={s} value={s}>{s}</option>)}
      </select>
    </div>
  )
}

function useDebounced(value, delay = 350) {
  const [v, setV] = useState(value)
  useEffect(() => {
    const t = setTimeout(() => setV(value), delay)
    return () => clearTimeout(t)
  }, [value, delay])
  return v
}

function EnquiryModal({ id, onClose }) {
  const toast = useToast()
  const queryClient = useQueryClient()
  const { data, isLoading } = useQuery({ queryKey: ['admin', 'enquiries', 'one', id], queryFn: () => api.get(`/admin/enquiries/${id}`).then((r) => r.data) })
  const [form, setForm] = useState(null)
  const [saving, setSaving] = useState(false)

  useEffect(() => {
    if (data) {
      setForm({ status: data.status, admin_note: data.admin_note || '' })
      queryClient.invalidateQueries({ queryKey: ['admin', 'enquiries'], exact: false, refetchType: 'none' })
    }
  }, [data, queryClient])

  async function save() {
    setSaving(true)
    try {
      await api.put(`/admin/enquiries/${id}`, form)
      toast('Enquiry updated.')
      queryClient.invalidateQueries({ queryKey: ['admin', 'enquiries'] })
      queryClient.invalidateQueries({ queryKey: ['admin', 'dashboard'] })
      onClose()
    } catch (e) {
      toast(errorMessage(e), 'error')
    }
    setSaving(false)
  }

  return (
    <Modal
      open
      onClose={() => { queryClient.invalidateQueries({ queryKey: ['admin', 'enquiries'] }); onClose() }}
      title={data ? data.subject : 'Enquiry'}
      footer={form && (
        <>
          <button type="button" className="btn-outline" onClick={onClose}>Close</button>
          <button type="button" className="btn-primary" onClick={save} disabled={saving}>{saving && <Spinner className="h-4 w-4 text-white" />} Save</button>
        </>
      )}
    >
      {isLoading || !form ? (
        <div className="flex justify-center py-8"><Spinner /></div>
      ) : (
        <div className="space-y-5 text-sm">
          <div className="grid gap-3 sm:grid-cols-2">
            <div><p className="text-xs text-muted">From</p><p className="font-medium text-navy-900">{data.name}</p></div>
            <div><p className="text-xs text-muted">Received</p><p>{new Date(data.created_at).toLocaleString()}</p></div>
            <div><p className="text-xs text-muted">Email</p><a className="flex items-center gap-1 text-brand-600" href={`mailto:${data.email}?subject=${encodeURIComponent('Re: ' + data.subject)}`}><Mail className="h-3.5 w-3.5" />{data.email}</a></div>
            {data.phone && <div><p className="text-xs text-muted">Phone</p><a className="flex items-center gap-1 text-brand-600" href={telHref(data.phone)}><Phone className="h-3.5 w-3.5" />{data.phone}</a></div>}
          </div>
          <div>
            <p className="text-xs text-muted">Message</p>
            <p className="mt-1 rounded-lg bg-slate-50 p-4 whitespace-pre-line text-ink">{data.message}</p>
          </div>
          <div className="grid gap-4 sm:grid-cols-[180px_1fr]">
            <div>
              <label htmlFor="enquiry-status" className="label">Status</label>
              <select id="enquiry-status" className="input" value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })}>
                {['new', 'read', 'replied', 'archived'].map((s) => <option key={s} value={s}>{s}</option>)}
              </select>
            </div>
            <div>
              <label htmlFor="enquiry-note" className="label">Internal note</label>
              <textarea id="enquiry-note" rows={3} className="input" value={form.admin_note} onChange={(e) => setForm({ ...form, admin_note: e.target.value })} />
            </div>
          </div>
          <p className="text-xs text-muted">IP {data.ip_address || '—'}</p>
        </div>
      )}
    </Modal>
  )
}

export function Enquiries() {
  const { can } = useAuth()
  const toast = useToast()
  const confirm = useConfirm()
  const queryClient = useQueryClient()
  const [term, setTerm] = useState('')
  const search = useDebounced(term)
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const [open, setOpen] = useState(null)
  const params = { page, search: search || undefined, status: status || undefined }
  const { data, isLoading, isError, refetch } = useList('enquiries', '/admin/enquiries', params, can('enquiries'))

  if (!can('enquiries')) return <NotAllowed />

  async function remove(row) {
    if (!(await confirm(`Delete the enquiry from ${row.name}?`))) return
    try {
      await api.delete(`/admin/enquiries/${row.id}`)
      toast('Enquiry deleted.')
      queryClient.invalidateQueries({ queryKey: ['admin', 'enquiries'] })
    } catch (e) {
      toast(errorMessage(e), 'error')
    }
  }

  return (
    <div>
      <PageHeader
        title="Contact enquiries"
        description="Messages submitted through the website contact form."
        actions={<button type="button" className="btn-outline" onClick={() => downloadCsv('/admin/enquiries/export', 'enquiries.csv', toast)}><Download className="h-4 w-4" aria-hidden="true" /> Export CSV</button>}
      />
      <Card>
        <SearchBar value={term} onChange={(v) => { setTerm(v); setPage(1) }} statuses={['new', 'read', 'replied', 'archived']} status={status} onStatus={(v) => { setStatus(v); setPage(1) }} placeholder="Search name, email, subject…" />
        {isLoading ? <div className="flex justify-center py-12"><Spinner /></div> : isError ? <ErrorState onRetry={refetch} /> : !data?.data?.length ? (
          <EmptyState title="No enquiries" message="New contact form messages will appear here." />
        ) : (
          <ul className="divide-y divide-slate-100">
            {data.data.map((row) => (
              <li key={row.id} className={cn('flex items-center gap-3 py-3', row.status === 'new' && 'font-semibold')}>
                <button type="button" onClick={() => setOpen(row.id)} className="min-w-0 flex-1 text-left">
                  <p className="truncate text-sm text-navy-900">{row.name} <span className="font-normal text-muted">· {row.email}</span></p>
                  <p className="truncate text-sm font-normal text-slate-600">{row.subject} — {row.message}</p>
                </button>
                <span className="hidden text-xs font-normal whitespace-nowrap text-muted sm:block">{formatDate(row.created_at)}</span>
                <Badge tone={statusTone(row.status)}>{row.status}</Badge>
                <button type="button" className="rounded-md p-2 text-slate-600 hover:bg-brand-50" onClick={() => setOpen(row.id)} aria-label="Open"><Eye className="h-4 w-4" /></button>
                <button type="button" className="rounded-md p-2 text-slate-600 hover:bg-red-50 hover:text-red-600" onClick={() => remove(row)} aria-label="Delete"><Trash2 className="h-4 w-4" /></button>
              </li>
            ))}
          </ul>
        )}
        <Pagination meta={data?.meta} onChange={setPage} className="mt-4" />
      </Card>
      {open && <EnquiryModal id={open} onClose={() => setOpen(null)} />}
    </div>
  )
}

function QuoteModal({ id, onClose }) {
  const toast = useToast()
  const queryClient = useQueryClient()
  const { data, isLoading } = useQuery({ queryKey: ['admin', 'quote-requests', 'one', id], queryFn: () => api.get(`/admin/quote-requests/${id}`).then((r) => r.data) })
  const [form, setForm] = useState(null)
  const [saving, setSaving] = useState(false)

  useEffect(() => {
    if (data) setForm({ status: data.status, admin_note: data.admin_note || '' })
  }, [data])

  async function save() {
    setSaving(true)
    try {
      await api.put(`/admin/quote-requests/${id}`, form)
      toast('Quote request updated.')
      queryClient.invalidateQueries({ queryKey: ['admin', 'quote-requests'] })
      queryClient.invalidateQueries({ queryKey: ['admin', 'dashboard'] })
      onClose()
    } catch (e) {
      toast(errorMessage(e), 'error')
    }
    setSaving(false)
  }

  const row = (label, value) => value ? (<div><p className="text-xs text-muted">{label}</p><p className="font-medium text-navy-900">{value}</p></div>) : null

  return (
    <Modal
      open
      onClose={() => { queryClient.invalidateQueries({ queryKey: ['admin', 'quote-requests'] }); onClose() }}
      title={data ? `Quote request #${data.id}` : 'Quote request'}
      footer={form && (
        <>
          <button type="button" className="btn-outline" onClick={onClose}>Close</button>
          <button type="button" className="btn-primary" onClick={save} disabled={saving}>{saving && <Spinner className="h-4 w-4 text-white" />} Save</button>
        </>
      )}
    >
      {isLoading || !form ? (
        <div className="flex justify-center py-8"><Spinner /></div>
      ) : (
        <div className="space-y-5 text-sm">
          <div className="grid gap-4 rounded-lg bg-brand-50/60 p-4 sm:grid-cols-2">
            {row('Product', data.product_name || 'Custom / not listed')}
            {row('Quantity', data.quantity)}
            {row('Specification', data.specification)}
            {row('Needed by', data.needed_by)}
          </div>
          <div className="grid gap-3 sm:grid-cols-2">
            {row('Name', data.name)}
            {row('Company', data.company)}
            <div><p className="text-xs text-muted">Email</p><a className="flex items-center gap-1 text-brand-600" href={`mailto:${data.email}?subject=${encodeURIComponent('Quotation: ' + (data.product_name || 'your request'))}`}><Mail className="h-3.5 w-3.5" />{data.email}</a></div>
            <div><p className="text-xs text-muted">Phone</p><a className="flex items-center gap-1 text-brand-600" href={telHref(data.phone)}><Phone className="h-3.5 w-3.5" />{data.phone}</a></div>
            {row('Received', new Date(data.created_at).toLocaleString())}
          </div>
          {data.message && (
            <div>
              <p className="text-xs text-muted">Additional details</p>
              <p className="mt-1 rounded-lg bg-slate-50 p-4 whitespace-pre-line text-ink">{data.message}</p>
            </div>
          )}
          <div className="grid gap-4 sm:grid-cols-[180px_1fr]">
            <div>
              <label htmlFor="quote-status" className="label">Status</label>
              <select id="quote-status" className="input" value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })}>
                {['new', 'quoted', 'ordered', 'closed'].map((s) => <option key={s} value={s}>{s}</option>)}
              </select>
            </div>
            <div>
              <label htmlFor="quote-note" className="label">Internal note (price offered, follow-up...)</label>
              <textarea id="quote-note" rows={3} className="input" value={form.admin_note} onChange={(e) => setForm({ ...form, admin_note: e.target.value })} />
            </div>
          </div>
        </div>
      )}
    </Modal>
  )
}

export function QuoteRequests() {
  const { can } = useAuth()
  const toast = useToast()
  const confirm = useConfirm()
  const queryClient = useQueryClient()
  const [term, setTerm] = useState('')
  const search = useDebounced(term)
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const [open, setOpen] = useState(null)
  const params = { page, search: search || undefined, status: status || undefined }
  const { data, isLoading, isError, refetch } = useList('quote-requests', '/admin/quote-requests', params, can('enquiries'))

  if (!can('enquiries')) return <NotAllowed />

  async function remove(row) {
    if (!(await confirm(`Delete the quote request from ${row.name}?`))) return
    try {
      await api.delete(`/admin/quote-requests/${row.id}`)
      toast('Quote request deleted.')
      queryClient.invalidateQueries({ queryKey: ['admin', 'quote-requests'] })
    } catch (e) {
      toast(errorMessage(e), 'error')
    }
  }

  return (
    <div>
      <PageHeader
        title="Quote requests"
        description="Quotation requests submitted from the website."
        actions={<button type="button" className="btn-outline" onClick={() => downloadCsv('/admin/quote-requests/export', 'quote-requests.csv', toast)}><Download className="h-4 w-4" aria-hidden="true" /> Export CSV</button>}
      />
      <Card>
        <SearchBar value={term} onChange={(v) => { setTerm(v); setPage(1) }} statuses={['new', 'quoted', 'ordered', 'closed']} status={status} onStatus={(v) => { setStatus(v); setPage(1) }} placeholder="Search name, company, product..." />
        {isLoading ? <div className="flex justify-center py-12"><Spinner /></div> : isError ? <ErrorState onRetry={refetch} /> : !data?.data?.length ? (
          <EmptyState title="No quote requests" message="Requests from the Request a Quote form will appear here." />
        ) : (
          <div className="-mx-5 overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-y border-slate-100 bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase">
                  <th className="px-5 py-2.5">Customer</th><th className="px-3 py-2.5">Product</th><th className="px-3 py-2.5">Quantity</th><th className="px-3 py-2.5">Date</th><th className="px-3 py-2.5">Status</th><th className="px-5 py-2.5 text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                {data.data.map((row) => (
                  <tr key={row.id} className={cn('border-b border-slate-100', !row.read_at && 'font-semibold')}>
                    <td className="px-5 py-2.5"><button type="button" className="text-left" onClick={() => setOpen(row.id)}><span className="block text-navy-900">{row.name}</span><span className="block text-xs font-normal text-muted">{row.company || row.email}</span></button></td>
                    <td className="px-3 py-2.5 text-slate-700">{row.product_name || 'Custom'}</td>
                    <td className="px-3 py-2.5 text-slate-700">{row.quantity}</td>
                    <td className="px-3 py-2.5 font-normal whitespace-nowrap text-muted">{formatDate(row.created_at)}</td>
                    <td className="px-3 py-2.5"><Badge tone={{ new: 'blue', quoted: 'amber', ordered: 'green', closed: 'slate' }[row.status]}>{row.status}</Badge></td>
                    <td className="px-5 py-2.5 text-right">
                      <button type="button" className="rounded-md p-2 text-slate-600 hover:bg-brand-50" onClick={() => setOpen(row.id)} aria-label="Open"><Eye className="h-4 w-4" /></button>
                      <button type="button" className="rounded-md p-2 text-slate-600 hover:bg-red-50 hover:text-red-600" onClick={() => remove(row)} aria-label="Delete"><Trash2 className="h-4 w-4" /></button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
        <Pagination meta={data?.meta} onChange={setPage} className="mt-4" />
      </Card>
      {open && <QuoteModal id={open} onClose={() => setOpen(null)} />}
    </div>
  )
}

export function Subscribers() {
  const { can } = useAuth()
  const toast = useToast()
  const confirm = useConfirm()
  const queryClient = useQueryClient()
  const [term, setTerm] = useState('')
  const search = useDebounced(term)
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const params = { page, search: search || undefined, status: status || undefined }
  const { data, isLoading, isError, refetch } = useList('subscribers', '/admin/subscribers', params, can('subscribers'))

  if (!can('subscribers')) return <NotAllowed />
  const refresh = () => queryClient.invalidateQueries({ queryKey: ['admin', 'subscribers'] })

  async function toggle(row) {
    try {
      await api.put(`/admin/subscribers/${row.id}`, { status: row.status === 'subscribed' ? 'unsubscribed' : 'subscribed' })
      toast('Subscriber updated.')
      refresh()
    } catch (e) {
      toast(errorMessage(e), 'error')
    }
  }

  async function remove(row) {
    if (!(await confirm(`Permanently delete ${row.email}?`))) return
    try {
      await api.delete(`/admin/subscribers/${row.id}`)
      toast('Subscriber deleted.')
      refresh()
    } catch (e) {
      toast(errorMessage(e), 'error')
    }
  }

  return (
    <div>
      <PageHeader
        title="Newsletter subscribers"
        actions={<button type="button" className="btn-outline" onClick={() => downloadCsv('/admin/subscribers/export', 'subscribers.csv', toast)}><Download className="h-4 w-4" aria-hidden="true" /> Export CSV</button>}
      />
      <Card>
        <SearchBar value={term} onChange={(v) => { setTerm(v); setPage(1) }} statuses={['subscribed', 'unsubscribed']} status={status} onStatus={(v) => { setStatus(v); setPage(1) }} placeholder="Search email…" />
        {isLoading ? <div className="flex justify-center py-12"><Spinner /></div> : isError ? <ErrorState onRetry={refetch} /> : !data?.data?.length ? (
          <EmptyState title="No subscribers yet" />
        ) : (
          <div className="-mx-5 overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-y border-slate-100 bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase">
                  <th className="px-5 py-2.5">Email</th><th className="px-3 py-2.5">Status</th><th className="px-3 py-2.5">Subscribed</th><th className="px-5 py-2.5 text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                {data.data.map((row) => (
                  <tr key={row.id} className="border-b border-slate-100">
                    <td className="px-5 py-2.5 font-medium text-navy-900">{row.email}</td>
                    <td className="px-3 py-2.5"><Badge tone={statusTone(row.status)}>{row.status}</Badge></td>
                    <td className="px-3 py-2.5 text-slate-600">{formatDate(row.subscribed_at)}</td>
                    <td className="px-5 py-2.5 text-right">
                      <button type="button" className="btn-outline mr-2 px-3 py-1.5 text-xs" onClick={() => toggle(row)}>{row.status === 'subscribed' ? 'Unsubscribe' : 'Resubscribe'}</button>
                      <button type="button" className="rounded-md p-2 text-slate-600 hover:bg-red-50 hover:text-red-600" onClick={() => remove(row)} aria-label={`Delete ${row.email}`}><Trash2 className="h-4 w-4" /></button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
        <Pagination meta={data?.meta} onChange={setPage} className="mt-4" />
      </Card>
    </div>
  )
}

export function MediaLibrary() {
  const { can, user } = useAuth()
  const toast = useToast()
  const confirm = useConfirm()
  const queryClient = useQueryClient()
  const [selected, setSelected] = useState(null)
  const canManage = can('media')
  const canUpload = canManage || user.permissions.some((p) => /^(pages|products|services|clients|team|news|history|quality|contact|settings)\./.test(p)) || user.is_super

  if (!canUpload) return <NotAllowed />

  async function remove(media) {
    if (!(await confirm(`Delete "${media.original_name}"? Any page still using this image will show an empty image.`))) return
    try {
      await api.delete(`/admin/media/${media.id}`)
      toast('File deleted.')
      setSelected(null)
      queryClient.invalidateQueries({ queryKey: ['admin', 'media'] })
    } catch (e) {
      toast(errorMessage(e), 'error')
    }
  }

  return (
    <div>
      <PageHeader title="Media library" description="Images are validated, re-encoded and resized on upload (JPG, PNG, WebP, GIF, ICO, max 5 MB). PDF brochures and datasheets up to 15 MB." actions={<UploadButton folder="general" />} />
      <Card>
        <MediaGrid onSelect={(m, opts) => !opts?.uploaded && setSelected(m)} />
      </Card>
      <Modal open={!!selected} onClose={() => setSelected(null)} title="Image details" size="lg">
        {selected && <MediaDetails media={selected} onClose={() => setSelected(null)} onDeleted={canManage ? remove : () => toast('Only users with Media permission can delete files.', 'error')} />}
      </Modal>
    </div>
  )
}
