import { useEffect, useMemo, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowDown, ArrowLeft, ArrowUp, Eye, EyeOff, ExternalLink, LayoutTemplate, Pencil, Plus, Trash2 } from 'lucide-react'
import { api, errorMessage, fieldErrors } from '../../lib/api'
import { cn } from '../../lib/utils'
import { ErrorState, Spinner } from '../../components/ui/States'
import { FieldGrid } from '../components/Fields'
import { Badge, Card, Modal, PageHeader, SaveButton, statusTone, useConfirm, useToast } from '../components/ui'
import { useAuth, useMeta } from '../auth'
import { NotAllowed } from './Misc'

const PAGE_FIELDS = [
  { name: 'title', label: 'Page title', type: 'text', required: true },
  { name: 'slug', label: 'URL slug', type: 'text', help: 'The page address, e.g. "our-mission" → /our-mission. Leave empty to generate.' },
  { name: 'template', label: 'Page type', type: 'select', required: true, options: [['sections', 'Content page (sections)'], ['products', 'Product catalog'], ['news', 'News listing'], ['contact', 'Contact page'], ['gallery', 'Photo gallery'], ['careers', 'Careers (job list)']] },
  { name: 'status', label: 'Status', type: 'select', required: true, options: [['draft', 'Draft (hidden)'], ['published', 'Published']] },
  { name: 'hero_title', label: 'Banner title', type: 'text', help: 'Defaults to the page title.' },
  { name: 'hero_subtitle', label: 'Banner subtitle', type: 'text' },
  { name: 'hero_image', label: 'Banner background image', type: 'image', folder: 'pages', span: 'full' },
  { name: 'content', label: 'Intro content (optional, shown under the banner)', type: 'richtext', span: 'full' },
  { name: 'published_at', label: 'Publish date', type: 'datetime' },
  { name: 'is_home', label: 'Use as homepage', type: 'toggle', default: false },
]

const SEO_FIELDS = [
  { name: 'meta_title', label: 'SEO title', type: 'text', span: 'full', help: 'Shown in Google results and browser tabs. ~60 characters.' },
  { name: 'meta_description', label: 'SEO description', type: 'textarea', span: 'full', help: '~155 characters.' },
  { name: 'meta_keywords', label: 'Keywords', type: 'text', span: 'full' },
  { name: 'og_image', label: 'Social share image', type: 'image', folder: 'pages', span: 'full' },
]

const FIELD_LABELS = {
  eyebrow: ['Small heading (eyebrow)', 'text'],
  title: ['Heading', 'text'],
  subtitle: ['Subtitle / supporting text', 'textarea'],
  content: ['Content', 'richtext'],
  image: ['Image', 'image'],
  image_alt: ['Image description (alt text)', 'text'],
  video_url: ['Video URL (YouTube/Vimeo, optional)', 'text'],
  button_label: ['Button label', 'text'],
  button_url: ['Button link', 'text'],
}

const ITEM_FIELD_LABELS = {
  icon: ['Icon', 'icon'],
  title: ['Title', 'text'],
  subtitle: ['Subtitle', 'text'],
  description: ['Description', 'textarea'],
  value: ['Value (e.g. 15+)', 'text'],
  image: ['Image', 'image'],
  link_url: ['Link', 'text'],
}

/** Builds form controls for section settings from backend validation rules. */
function settingField(key, rule) {
  const label = key.replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase())
  if (rule.includes('boolean')) return { name: key, label, type: 'toggle' }
  if (rule.includes('integer')) {
    const min = rule.match(/min:(\d+)/)?.[1]
    const max = rule.match(/max:(\d+)/)?.[1]
    return { name: key, label, type: 'number', min, max, help: min && max ? `${min}–${max}` : undefined }
  }
  const options = rule.match(/in:([^|]+)/)?.[1]?.split(',')
  if (options) return { name: key, label, type: 'select', options: options.map((o) => [o, o.replace(/_/g, ' ')]) }
  if (key === 'card_icon') return { name: key, label, type: 'icon' }
  return { name: key, label, type: key === 'card_text' ? 'textarea' : 'text' }
}

function SectionEditor({ section, type, onClose, onSaved }) {
  const toast = useToast()
  const [record, setRecord] = useState(() => ({ ...section, settings: { ...(section.settings || {}) }, items: section.items || [] }))
  const [settings, setSettings] = useState(() => ({ ...(section.settings || {}) }))
  const [errors, setErrors] = useState({})
  const [saving, setSaving] = useState(false)

  const fields = [
    { name: 'admin_label', label: 'Admin label (only visible here)', type: 'text', span: 'full' },
    ...type.fields.map((f) => ({ name: f, label: FIELD_LABELS[f][0], type: FIELD_LABELS[f][1], span: ['content', 'subtitle', 'image', 'title'].includes(f) ? 'full' : undefined, folder: 'pages' })),
    { name: 'is_active', label: 'Visible on website', type: 'toggle' },
  ]
  const settingFields = Object.entries(type.settings || {}).map(([k, rule]) => settingField(k, rule))
  const itemField = type.items && {
    name: 'items',
    label: 'Items',
    type: 'repeater',
    span: 'full',
    addLabel: 'Add item',
    defaults: { is_active: true },
    fields: type.items.map((f) => ({ name: f, label: ITEM_FIELD_LABELS[f][0], type: ITEM_FIELD_LABELS[f][1], folder: 'pages' })),
  }

  async function save() {
    setSaving(true)
    setErrors({})
    const payload = { ...record, settings }
    delete payload.data
    payload.items = type.items ? (record.items || []).map(({ id, title, subtitle, description, value, icon, image, link_url, is_active }) => ({ id, title, subtitle, description, value, icon, image, link_url, is_active: is_active !== false })) : undefined
    try {
      const { data } = await api.put(`/admin/sections/${section.id}`, payload)
      toast('Section saved.')
      onSaved(data)
    } catch (e) {
      const errs = fieldErrors(e)
      setErrors(Object.fromEntries(Object.entries(errs).map(([k, v]) => [k.replace(/^settings\./, ''), v])))
      toast(errorMessage(e, 'Could not save the section.'), 'error')
    }
    setSaving(false)
  }

  return (
    <Modal
      open
      onClose={onClose}
      title={`Edit section: ${type.label}`}
      size="xl"
      footer={
        <>
          <button type="button" className="btn-outline" onClick={onClose}>Cancel</button>
          <button type="button" className="btn-primary" onClick={save} disabled={saving}>{saving && <Spinner className="h-4 w-4 text-white" />} Save section</button>
        </>
      }
    >
      <p className="mb-5 rounded-md bg-brand-50 px-3 py-2 text-sm text-brand-800">{type.description}</p>
      <div className="space-y-6">
        <FieldGrid fields={fields} record={record} setRecord={setRecord} errors={errors} idPrefix={`section-${section.id}`} />
        {settingFields.length > 0 && (
          <div>
            <h3 className="mb-3 text-sm font-semibold text-navy-900">Layout options</h3>
            <FieldGrid fields={settingFields} record={settings} setRecord={setSettings} errors={errors} idPrefix={`section-${section.id}-settings`} />
          </div>
        )}
        {itemField && (
          <div>
            <h3 className="mb-3 text-sm font-semibold text-navy-900">Items</h3>
            <FieldGrid fields={[itemField]} record={record} setRecord={setRecord} errors={errors} idPrefix={`section-${section.id}-items`} />
          </div>
        )}
      </div>
    </Modal>
  )
}

function SectionBuilder({ page }) {
  const { data: meta } = useMeta()
  const toast = useToast()
  const confirm = useConfirm()
  const queryClient = useQueryClient()
  const [editing, setEditing] = useState(null)
  const [newType, setNewType] = useState('')
  const key = ['admin', 'sections', page.id]
  const { data: sections = [], isLoading, isError, refetch } = useQuery({ queryKey: key, queryFn: () => api.get(`/admin/pages/${page.id}/sections`).then((r) => r.data) })
  const types = meta?.section_types || {}
  const refresh = () => {
    queryClient.invalidateQueries({ queryKey: key })
    queryClient.invalidateQueries({ queryKey: ['page'] })
  }

  async function add() {
    if (!newType) return
    try {
      const { data } = await api.post(`/admin/pages/${page.id}/sections`, { type: newType, is_active: true })
      setNewType('')
      refresh()
      setEditing(data)
    } catch (e) {
      toast(errorMessage(e), 'error')
    }
  }

  async function move(index, dir) {
    const ids = sections.map((s) => s.id)
    const [id] = ids.splice(index, 1)
    ids.splice(index + dir, 0, id)
    queryClient.setQueryData(key, ids.map((i) => sections.find((s) => s.id === i)))
    try {
      await api.post(`/admin/pages/${page.id}/sections/reorder`, { ids })
      refresh()
    } catch (e) {
      toast(errorMessage(e), 'error')
      refresh()
    }
  }

  async function toggle(section) {
    try {
      await api.put(`/admin/sections/${section.id}`, { is_active: !section.is_active, settings: section.settings })
      toast(section.is_active ? 'Section hidden.' : 'Section visible.')
      refresh()
    } catch (e) {
      toast(errorMessage(e), 'error')
    }
  }

  async function remove(section) {
    if (!(await confirm(`Delete the "${types[section.type]?.label || section.type}" section and its items?`))) return
    try {
      await api.delete(`/admin/sections/${section.id}`)
      toast('Section deleted.')
      refresh()
    } catch (e) {
      toast(errorMessage(e), 'error')
    }
  }

  return (
    <Card
      title="Page sections"
      actions={
        <div className="flex gap-2">
          <select aria-label="Section type to add" className="input py-1.5 text-sm" value={newType} onChange={(e) => setNewType(e.target.value)}>
            <option value="">Add a section…</option>
            {Object.entries(types).map(([k, t]) => (
              <option key={k} value={k}>{t.label}</option>
            ))}
          </select>
          <button type="button" className="btn-primary py-1.5" onClick={add} disabled={!newType}>
            <Plus className="h-4 w-4" aria-hidden="true" /> Add
          </button>
        </div>
      }
    >
      {isLoading ? (
        <div className="flex justify-center py-8"><Spinner /></div>
      ) : isError ? (
        <ErrorState onRetry={refetch} />
      ) : sections.length === 0 ? (
        <p className="py-6 text-center text-sm text-muted">No sections yet. Add one above to start building this page.</p>
      ) : (
        <ol className="space-y-2">
          {sections.map((s, i) => (
            <li key={s.id} className={cn('flex items-center gap-3 rounded-lg border px-3 py-2.5', s.is_active ? 'border-slate-200 bg-white' : 'border-dashed border-slate-300 bg-slate-50 opacity-70')}>
              <div className="flex flex-col">
                <button type="button" className="rounded p-0.5 hover:bg-slate-200 disabled:opacity-20" disabled={i === 0} onClick={() => move(i, -1)} aria-label="Move section up"><ArrowUp className="h-3.5 w-3.5" /></button>
                <button type="button" className="rounded p-0.5 hover:bg-slate-200 disabled:opacity-20" disabled={i === sections.length - 1} onClick={() => move(i, 1)} aria-label="Move section down"><ArrowDown className="h-3.5 w-3.5" /></button>
              </div>
              <LayoutTemplate className="h-5 w-5 shrink-0 text-brand-600" aria-hidden="true" />
              <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium text-navy-900">{s.admin_label || s.title || types[s.type]?.label || s.type}</p>
                <p className="truncate text-xs text-muted">
                  {types[s.type]?.label || s.type}
                  {s.items?.length ? ` · ${s.items.length} items` : ''}
                  {!s.is_active && ' · hidden'}
                </p>
              </div>
              <button type="button" className="rounded-md p-2 text-slate-600 hover:bg-slate-100" onClick={() => toggle(s)} aria-label={s.is_active ? 'Hide section' : 'Show section'} title={s.is_active ? 'Hide' : 'Show'}>
                {s.is_active ? <Eye className="h-4 w-4" /> : <EyeOff className="h-4 w-4" />}
              </button>
              <button type="button" className="rounded-md p-2 text-slate-600 hover:bg-brand-50 hover:text-brand-700" onClick={() => setEditing(s)} aria-label="Edit section"><Pencil className="h-4 w-4" /></button>
              <button type="button" className="rounded-md p-2 text-slate-600 hover:bg-red-50 hover:text-red-600" onClick={() => remove(s)} aria-label="Delete section"><Trash2 className="h-4 w-4" /></button>
            </li>
          ))}
        </ol>
      )}
      {editing && types[editing.type] && (
        <SectionEditor
          key={editing.id}
          section={editing}
          type={types[editing.type]}
          onClose={() => setEditing(null)}
          onSaved={() => {
            setEditing(null)
            refresh()
          }}
        />
      )}
    </Card>
  )
}

export default function PageEditor() {
  const { id } = useParams()
  const isNew = id === 'new'
  const navigate = useNavigate()
  const toast = useToast()
  const queryClient = useQueryClient()
  const { can } = useAuth()
  const { data: meta } = useMeta()
  const [record, setRecord] = useState({ template: 'sections', status: 'draft', is_home: false })
  const [errors, setErrors] = useState({})
  const [saving, setSaving] = useState(false)

  const { data, isLoading, isError, refetch } = useQuery({
    queryKey: ['admin', 'pages', 'one', id],
    queryFn: () => api.get(`/admin/pages/${id}`).then((r) => r.data),
    enabled: !isNew,
  })
  useEffect(() => {
    if (data) setRecord(data)
  }, [data])

  const fields = useMemo(
    () => PAGE_FIELDS.map((f) => (f.name === 'template' && record.is_system ? { ...f, help: 'Built-in page: the type cannot be changed.' } : f)),
    [record.is_system],
  )

  if (!can('pages')) return <NotAllowed />
  if (!isNew && isLoading) return <div className="flex justify-center py-20"><Spinner /></div>
  if (!isNew && isError) return <ErrorState onRetry={refetch} />

  async function submit(e) {
    e.preventDefault()
    setSaving(true)
    setErrors({})
    const payload = Object.fromEntries([...PAGE_FIELDS, ...SEO_FIELDS].map((f) => [f.name, record[f.name] ?? null]))
    payload.is_home = !!record.is_home
    try {
      const res = isNew ? await api.post('/admin/pages', payload) : await api.put(`/admin/pages/${id}`, payload)
      toast('Page saved.')
      queryClient.invalidateQueries({ queryKey: ['admin', 'pages'] })
      queryClient.invalidateQueries({ queryKey: ['page'] })
      queryClient.invalidateQueries({ queryKey: ['navigation'] })
      if (isNew) navigate(`/admin/pages/${res.data.id}`, { replace: true })
      else setRecord(res.data)
    } catch (err) {
      setErrors(fieldErrors(err))
      toast(errorMessage(err, 'Could not save the page.'), 'error')
    }
    setSaving(false)
  }

  const viewUrl = record.path && record.status === 'published' ? record.path : null

  return (
    <div className="space-y-6">
      <form onSubmit={submit} noValidate className="space-y-6">
        <PageHeader
          title={isNew ? 'New page' : record.title}
          description={!isNew && (
            <span className="flex items-center gap-2">
              <Badge tone={statusTone(record.status)}>{record.status}</Badge> {record.path}
            </span>
          )}
          actions={
            <>
              <Link to="/admin/r/pages" className="btn-outline"><ArrowLeft className="h-4 w-4" aria-hidden="true" /> Pages</Link>
              {viewUrl && (
                <a href={viewUrl} target="_blank" rel="noopener noreferrer" className="btn-outline"><ExternalLink className="h-4 w-4" aria-hidden="true" /> View</a>
              )}
              <SaveButton saving={saving} />
            </>
          }
        />
        <Card title="Page details">
          <FieldGrid fields={fields} record={record} setRecord={setRecord} errors={errors} idPrefix="page" />
        </Card>
        <Card title="Search engine optimisation">
          <FieldGrid fields={SEO_FIELDS} record={record} setRecord={setRecord} errors={errors} idPrefix="page-seo" />
        </Card>
      </form>
      {!isNew && meta && record.id && <SectionBuilder key={record.id} page={record} />}
      {isNew && <p className="text-sm text-muted">Save the page first, then add content sections.</p>}
    </div>
  )
}
