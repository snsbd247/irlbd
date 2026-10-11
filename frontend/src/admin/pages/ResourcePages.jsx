import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowDown, ArrowLeft, ArrowUp, Check, Minus, Pencil, Plus, Search, Trash2 } from 'lucide-react'
import { api, errorMessage, fieldErrors } from '../../lib/api'
import { Icon } from '../../lib/icons'
import { cn, formatDate } from '../../lib/utils'
import { EmptyState, ErrorState, Spinner } from '../../components/ui/States'
import Pagination from '../../components/ui/Pagination'
import { RESOURCES } from '../resources'
import { FieldGrid } from '../components/Fields'
import { Badge, Card, PageHeader, SaveButton, statusTone, useConfirm, useToast } from '../components/ui'
import { useAuth } from '../auth'
import { NotAllowed } from './Misc'

function Cell({ column, row }) {
  const value = row[column.key]
  switch (column.type) {
    case 'image':
      return value ? <img src={value} alt="" className="h-11 w-14 rounded-md border border-slate-100 bg-slate-50 object-contain" /> : <span className="block h-11 w-14 rounded-md bg-slate-100" />
    case 'icon':
      return <span className="icon-circle h-9 w-9 [&>svg]:h-4 [&>svg]:w-4"><Icon name={value} /></span>
    case 'bool':
      return value ? <Check className="h-4 w-4 text-emerald-600" aria-label="Yes" /> : <Minus className="h-4 w-4 text-slate-300" aria-label="No" />
    case 'status':
      return <Badge tone={statusTone(value)}>{value}</Badge>
    case 'date':
      return <span className="whitespace-nowrap text-slate-600">{formatDate(value) || '—'}</span>
    default: {
      const text = column.render ? column.render(row) : value
      return (
        <div className="min-w-0">
          <p className="truncate font-medium text-navy-900">{text ?? '—'}</p>
          {column.sub && column.sub(row) && <p className="truncate text-xs text-muted">{column.sub(row)}</p>}
        </div>
      )
    }
  }
}

function FilterSelect({ filter, value, onChange }) {
  const { data = [] } = useQuery({
    queryKey: ['admin', 'options', filter.endpoint],
    queryFn: () => api.get(filter.endpoint, { params: { all: 1 } }).then((r) => r.data.data ?? r.data),
    enabled: !!filter.endpoint,
  })
  const options = filter.options || data.map((o) => [o.id, o.name ?? o.title])
  return (
    <select aria-label={filter.label} className="input sm:w-44" value={value || ''} onChange={(e) => onChange(e.target.value)}>
      <option value="">All {filter.label.toLowerCase()}</option>
      {options.map(([v, l]) => (
        <option key={v} value={v}>{l}</option>
      ))}
    </select>
  )
}

export function ResourceList({ resourceKey }) {
  const config = RESOURCES[resourceKey]
  const { can } = useAuth()
  const toast = useToast()
  const confirm = useConfirm()
  const queryClient = useQueryClient()
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')
  const [filters, setFilters] = useState({})
  const [term, setTerm] = useState('')

  useEffect(() => {
    const t = setTimeout(() => { setSearch(term); setPage(1) }, 350)
    return () => clearTimeout(t)
  }, [term])

  const params = { page, search: search || undefined, ...filters, per_page: 25 }
  const queryKey = ['admin', resourceKey, params]
  const { data, isLoading, isError, refetch, isFetching } = useQuery({
    queryKey,
    queryFn: () => api.get(`/admin/${resourceKey}`, { params }).then((r) => r.data),
    placeholderData: (prev) => prev,
    enabled: can(config.permission),
  })

  if (!can(config.permission)) return <NotAllowed />

  const rows = data?.data || []
  const canReorder = config.sortable && !search && !Object.values(filters).some(Boolean)
  const editPath = (row) => (config.editPath ? config.editPath(row) : `/admin/r/${resourceKey}/${row.id}`)

  async function remove(row) {
    const label = row.name || row.title || row.question || row.label || `#${row.id}`
    if (!(await confirm(`Delete "${label}"? This cannot be undone.`))) return
    try {
      await api.delete(`/admin/${resourceKey}/${row.id}`)
      toast('Deleted.')
      queryClient.invalidateQueries({ queryKey: ['admin', resourceKey] })
    } catch (e) {
      toast(errorMessage(e), 'error')
    }
  }

  async function move(index, dir) {
    const ids = rows.map((r) => r.id)
    const [id] = ids.splice(index, 1)
    ids.splice(index + dir, 0, id)
    // Persist the order of the whole visible page (offset by previous pages).
    const offset = ((data.meta?.current_page || 1) - 1) * (data.meta?.per_page || 25)
    try {
      if (offset === 0) {
        await api.post(`/admin/${resourceKey}/reorder`, { ids })
      } else {
        const all = await api.get(`/admin/${resourceKey}`, { params: { all: 1 } }).then((r) => r.data.data ?? r.data)
        const allIds = all.map((r) => r.id)
        allIds.splice(offset, ids.length, ...ids)
        await api.post(`/admin/${resourceKey}/reorder`, { ids: allIds })
      }
      queryClient.invalidateQueries({ queryKey: ['admin', resourceKey] })
    } catch (e) {
      toast(errorMessage(e), 'error')
    }
  }

  return (
    <div>
      <PageHeader
        title={config.title}
        description={config.description}
        actions={
          <Link to={config.newPath || `/admin/r/${resourceKey}/new`} className="btn-primary">
            <Plus className="h-4 w-4" aria-hidden="true" /> New {config.singular}
          </Link>
        }
      />
      <Card>
        <div className="mb-4 flex flex-col gap-2 sm:flex-row">
          <div className="relative flex-1">
            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
            <input type="search" aria-label={`Search ${config.title}`} className="input pl-9" placeholder="Search…" value={term} onChange={(e) => setTerm(e.target.value)} />
          </div>
          {(config.filters || []).map((f) => (
            <FilterSelect key={f.name} filter={f} value={filters[f.name]} onChange={(v) => { setFilters((s) => ({ ...s, [f.name]: v || undefined })); setPage(1) }} />
          ))}
        </div>

        {isLoading ? (
          <div className="flex justify-center py-16"><Spinner /></div>
        ) : isError ? (
          <ErrorState onRetry={refetch} />
        ) : rows.length === 0 ? (
          <EmptyState title={`No ${config.title.toLowerCase()} found`} message={search ? 'Try a different search.' : `Create the first ${config.singular}.`} />
        ) : (
          <div className={cn('-mx-5 overflow-x-auto', isFetching && 'opacity-60')}>
            <table className="w-full text-sm">
              <thead>
                <tr className="border-y border-slate-100 bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                  {canReorder && <th className="w-16 px-3 py-2.5"><span className="sr-only">Order</span></th>}
                  {config.columns.map((c, i) => (
                    <th key={c.key + i} className="px-3 py-2.5 first:pl-5">{c.label || <span className="sr-only">{c.key}</span>}</th>
                  ))}
                  <th className="px-5 py-2.5 text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((row, index) => (
                  <tr key={row.id} className="border-b border-slate-100 last:border-0 hover:bg-slate-50/60">
                    {canReorder && (
                      <td className="px-3 py-2">
                        <div className="flex flex-col">
                          <button type="button" className="rounded p-0.5 text-slate-500 hover:bg-slate-200 disabled:opacity-20" disabled={index === 0} onClick={() => move(index, -1)} aria-label="Move up"><ArrowUp className="h-3.5 w-3.5" /></button>
                          <button type="button" className="rounded p-0.5 text-slate-500 hover:bg-slate-200 disabled:opacity-20" disabled={index === rows.length - 1} onClick={() => move(index, 1)} aria-label="Move down"><ArrowDown className="h-3.5 w-3.5" /></button>
                        </div>
                      </td>
                    )}
                    {config.columns.map((c, i) => (
                      <td key={c.key + i} className="max-w-xs px-3 py-2.5 first:pl-5">
                        <Cell column={c} row={row} />
                      </td>
                    ))}
                    <td className="px-5 py-2.5">
                      <div className="flex justify-end gap-1">
                        <Link to={editPath(row)} className="rounded-md p-2 text-slate-600 hover:bg-brand-50 hover:text-brand-700" aria-label="Edit">
                          <Pencil className="h-4 w-4" aria-hidden="true" />
                        </Link>
                        {(config.rowActions || []).filter((a) => !a.show || a.show(row)).map((a) => (
                          <button
                            key={a.label}
                            type="button"
                            className="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-amber-50 hover:text-amber-700"
                            onClick={async () => {
                              if (a.confirm && !(await confirm(a.confirm(row), { title: a.label, confirmLabel: a.label }))) return
                              try {
                                const [method, url] = a.request(row)
                                const res = await api.request({ method, url })
                                toast(res.data?.message || 'Done.')
                                queryClient.invalidateQueries({ queryKey: ['admin', resourceKey] })
                              } catch (e) {
                                toast(errorMessage(e), 'error')
                              }
                            }}
                          >
                            {a.label}
                          </button>
                        ))}
                        {(!config.canDelete || config.canDelete(row)) && (
                          <button type="button" onClick={() => remove(row)} className="rounded-md p-2 text-slate-600 hover:bg-red-50 hover:text-red-600" aria-label="Delete">
                            <Trash2 className="h-4 w-4" aria-hidden="true" />
                          </button>
                        )}
                      </div>
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

export function defaultsFor(fields) {
  return Object.fromEntries(fields.filter((f) => f.default !== undefined).map((f) => [f.name, f.default]))
}

/** Converts an API record into the editable shape expected by the form. */
function toForm(record) {
  const out = { ...record }
  if (Array.isArray(record.permissions)) out.permissions = record.permissions
  return out
}

/** Only fields declared in the schema are sent back to the API. */
function toPayload(fields, record) {
  const payload = {}
  fields.forEach((f) => {
    if (f.showIf && !f.showIf(record)) return
    let v = record[f.name]
    if (f.type === 'gallery') v = (v || []).map(({ path, alt }) => ({ path, alt }))
    if (f.type === 'password' && !v) return
    payload[f.name] = v === undefined ? null : v
  })
  return payload
}

export function ResourceForm({ resourceKey }) {
  const config = RESOURCES[resourceKey]
  const { id } = useParams()
  const isNew = id === 'new'
  const navigate = useNavigate()
  const toast = useToast()
  const queryClient = useQueryClient()
  const { can } = useAuth()
  const [record, setRecord] = useState(() => defaultsFor(config.fields))
  const [errors, setErrors] = useState({})
  const [saving, setSaving] = useState(false)

  const { data, isLoading, isError, refetch } = useQuery({
    queryKey: ['admin', resourceKey, 'one', id],
    queryFn: () => api.get(`/admin/${resourceKey}/${id}`).then((r) => r.data),
    enabled: !isNew && can(config.permission),
  })

  useEffect(() => {
    if (data) setRecord(toForm(data))
  }, [data])

  if (!can(config.permission)) return <NotAllowed />

  async function submit(e) {
    e.preventDefault()
    setSaving(true)
    setErrors({})
    try {
      const payload = toPayload(config.fields, record)
      const res = isNew ? await api.post(`/admin/${resourceKey}`, payload) : await api.put(`/admin/${resourceKey}/${id}`, payload)
      queryClient.invalidateQueries({ queryKey: ['admin', resourceKey] })
      queryClient.invalidateQueries({ queryKey: ['admin', 'options'] })
      toast(`${config.singular[0].toUpperCase()}${config.singular.slice(1)} saved.`)
      if (isNew) navigate(`/admin/r/${resourceKey}/${res.data.id}`, { replace: true })
      else setRecord(toForm(res.data))
    } catch (err) {
      setErrors(fieldErrors(err))
      toast(errorMessage(err, 'Could not save. Please check the form.'), 'error')
    } finally {
      setSaving(false)
    }
  }

  if (!isNew && isLoading) return <div className="flex justify-center py-20"><Spinner /></div>
  if (!isNew && isError) return <ErrorState onRetry={refetch} />

  const main = config.fields.filter((f) => f.group !== 'SEO')
  const seoFields = config.fields.filter((f) => f.group === 'SEO')

  return (
    <form onSubmit={submit} noValidate>
      <PageHeader
        title={isNew ? `New ${config.singular}` : `Edit ${config.singular}`}
        description={!isNew && (record.name || record.title || record.question)}
        actions={
          <>
            <Link to={`/admin/r/${resourceKey}`} className="btn-outline"><ArrowLeft className="h-4 w-4" aria-hidden="true" /> Back</Link>
            <SaveButton saving={saving} />
          </>
        }
      />
      {Object.keys(errors).length > 0 && (
        <div role="alert" className="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">Please fix the highlighted fields.</div>
      )}
      <div className="space-y-6">
        <Card>
          <FieldGrid fields={main} record={record} setRecord={setRecord} errors={errors} idPrefix={resourceKey} />
        </Card>
        {seoFields.length > 0 && (
          <Card title="Search engine optimisation">
            <FieldGrid fields={seoFields} record={record} setRecord={setRecord} errors={errors} idPrefix={`${resourceKey}-seo`} />
          </Card>
        )}
      </div>
      <div className="mt-6 flex justify-end">
        <SaveButton saving={saving} />
      </div>
    </form>
  )
}
