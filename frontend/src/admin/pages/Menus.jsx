import { useState } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowDown, ArrowUp, CornerDownRight, ExternalLink, EyeOff, Pencil, Plus, Trash2 } from 'lucide-react'
import { api, errorMessage, fieldErrors } from '../../lib/api'
import { cn } from '../../lib/utils'
import { ErrorState, Spinner } from '../../components/ui/States'
import { FieldGrid } from '../components/Fields'
import { Card, Modal, PageHeader, useConfirm, useToast } from '../components/ui'
import { useAuth } from '../auth'
import { NotAllowed } from './Misc'

const LOCATIONS = [
  ['header', 'Header menu', 'Main navigation. Items can have one level of sub-items (dropdown).'],
  ['footer_quick', 'Footer: Quick Links', ''],
  ['footer_products', 'Footer: Our Products', ''],
]

function MenuItemModal({ item, location, parents, onClose, onSaved }) {
  const toast = useToast()
  const [record, setRecord] = useState(() =>
    item ? { ...item, label_bn: item.translations?.bn?.label || '' } : { location, link_type: 'page', is_active: true, open_in_new_tab: false, parent_id: null, label_bn: '' },
  )
  const [errors, setErrors] = useState({})
  const [saving, setSaving] = useState(false)

  const fields = [
    { name: 'label', label: 'Label', type: 'text', required: true },
    { name: 'label_bn', label: 'Label (বাংলা)', type: 'text', placeholder: record.label || '', help: 'Optional. Shown when the website is switched to বাংলা.' },
    { name: 'link_type', label: 'Link to', type: 'select', required: true, options: [['page', 'A website page'], ['url', 'Custom URL']] },
    { name: 'page_id', label: 'Page', type: 'relation', endpoint: '/admin/pages', labelKey: 'title', showIf: (r) => r.link_type === 'page' },
    { name: 'url', label: 'URL', type: 'text', placeholder: '/products?category=… or https://…', help: 'Relative paths (/…), https://, mailto: or tel: links.', showIf: (r) => r.link_type === 'url' },
    ...(location === 'header' ? [{ name: 'parent_id', label: 'Parent item (for dropdowns)', type: 'select', options: parents.filter((p) => p.id !== item?.id).map((p) => [p.id, p.label]), placeholder: '— Top level —' }] : []),
    { name: 'open_in_new_tab', label: 'Open in new tab', type: 'toggle' },
    { name: 'is_active', label: 'Visible', type: 'toggle' },
  ]

  async function save() {
    setSaving(true)
    setErrors({})
    const payload = {
      location,
      label: record.label,
      link_type: record.link_type,
      page_id: record.link_type === 'page' ? record.page_id : null,
      url: record.link_type === 'url' ? record.url : null,
      parent_id: record.parent_id ? Number(record.parent_id) : null,
      open_in_new_tab: !!record.open_in_new_tab,
      is_active: record.is_active !== false,
      translations: { bn: { label: record.label_bn || '' } },
    }
    try {
      if (item) await api.put(`/admin/menu-items/${item.id}`, payload)
      else await api.post('/admin/menu-items', payload)
      toast('Menu item saved.')
      onSaved()
    } catch (e) {
      setErrors(fieldErrors(e))
      toast(errorMessage(e), 'error')
    }
    setSaving(false)
  }

  return (
    <Modal
      open
      onClose={onClose}
      title={item ? 'Edit menu item' : 'Add menu item'}
      size="md"
      footer={
        <>
          <button type="button" className="btn-outline" onClick={onClose}>Cancel</button>
          <button type="button" className="btn-primary" onClick={save} disabled={saving}>{saving && <Spinner className="h-4 w-4 text-white" />} Save</button>
        </>
      }
    >
      <FieldGrid fields={fields.map((f) => ({ ...f, span: 'full' }))} record={record} setRecord={setRecord} errors={errors} idPrefix="menu" />
    </Modal>
  )
}

export default function Menus() {
  const { can } = useAuth()
  const toast = useToast()
  const confirm = useConfirm()
  const queryClient = useQueryClient()
  const [location, setLocation] = useState('header')
  const [editing, setEditing] = useState(null) // null | 'new' | item
  const key = ['admin', 'menu-items', location]
  const { data: items = [], isLoading, isError, refetch } = useQuery({
    queryKey: key,
    queryFn: () => api.get('/admin/menu-items', { params: { location, all: 1 } }).then((r) => r.data),
    enabled: can('menus'),
  })

  if (!can('menus')) return <NotAllowed />

  const roots = items.filter((i) => !i.parent_id)
  const childrenOf = (id) => items.filter((i) => i.parent_id === id)
  const refresh = () => {
    queryClient.invalidateQueries({ queryKey: ['admin', 'menu-items'] })
    queryClient.invalidateQueries({ queryKey: ['navigation'] })
  }

  async function move(siblings, index, dir) {
    const ids = siblings.map((s) => s.id)
    const [id] = ids.splice(index, 1)
    ids.splice(index + dir, 0, id)
    try {
      await api.post('/admin/menu-items/reorder', { ids })
      refresh()
    } catch (e) {
      toast(errorMessage(e), 'error')
    }
  }

  async function remove(item) {
    const kids = childrenOf(item.id).length
    if (!(await confirm(`Delete "${item.label}"${kids ? ` and its ${kids} sub-item(s)` : ''}?`))) return
    try {
      await api.delete(`/admin/menu-items/${item.id}`)
      toast('Menu item deleted.')
      refresh()
    } catch (e) {
      toast(errorMessage(e), 'error')
    }
  }

  const Row = ({ item, siblings, index, child }) => (
    <li className={cn('flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2', child && 'ml-8')}>
      <div className="flex flex-col">
        <button type="button" className="rounded p-0.5 hover:bg-slate-200 disabled:opacity-20" disabled={index === 0} onClick={() => move(siblings, index, -1)} aria-label="Move up"><ArrowUp className="h-3.5 w-3.5" /></button>
        <button type="button" className="rounded p-0.5 hover:bg-slate-200 disabled:opacity-20" disabled={index === siblings.length - 1} onClick={() => move(siblings, index, 1)} aria-label="Move down"><ArrowDown className="h-3.5 w-3.5" /></button>
      </div>
      {child && <CornerDownRight className="h-4 w-4 text-slate-400" aria-hidden="true" />}
      <div className="min-w-0 flex-1">
        <p className="flex items-center gap-2 text-sm font-medium text-navy-900">
          {item.label}
          {!item.is_active && <EyeOff className="h-3.5 w-3.5 text-slate-400" aria-label="Hidden" />}
          {item.open_in_new_tab && <ExternalLink className="h-3.5 w-3.5 text-slate-400" aria-label="Opens in new tab" />}
        </p>
        <p className="truncate text-xs text-muted">{item.href || '(page missing)'}</p>
      </div>
      <button type="button" className="rounded-md p-2 text-slate-600 hover:bg-brand-50 hover:text-brand-700" onClick={() => setEditing(item)} aria-label={`Edit ${item.label}`}><Pencil className="h-4 w-4" /></button>
      <button type="button" className="rounded-md p-2 text-slate-600 hover:bg-red-50 hover:text-red-600" onClick={() => remove(item)} aria-label={`Delete ${item.label}`}><Trash2 className="h-4 w-4" /></button>
    </li>
  )

  const current = LOCATIONS.find(([k]) => k === location)

  return (
    <div>
      <PageHeader
        title="Menus"
        description="Manage header and footer navigation. Links to pages update automatically when a page's URL changes."
        actions={<button type="button" className="btn-primary" onClick={() => setEditing('new')}><Plus className="h-4 w-4" aria-hidden="true" /> Add item</button>}
      />
      <div className="mb-4 flex flex-wrap gap-2" role="tablist">
        {LOCATIONS.map(([k, label]) => (
          <button key={k} type="button" role="tab" aria-selected={location === k} onClick={() => setLocation(k)} className={cn('rounded-md px-4 py-2 text-sm font-medium', location === k ? 'bg-navy-900 text-white' : 'bg-white text-slate-700 hover:bg-slate-100')}>
            {label}
          </button>
        ))}
      </div>
      <Card title={current[1]}>
        {current[2] && <p className="mb-4 text-sm text-muted">{current[2]}</p>}
        {isLoading ? (
          <div className="flex justify-center py-8"><Spinner /></div>
        ) : isError ? (
          <ErrorState onRetry={refetch} />
        ) : roots.length === 0 ? (
          <p className="py-6 text-center text-sm text-muted">No items in this menu yet.</p>
        ) : (
          <ul className="space-y-2">
            {roots.map((item, i) => (
              <li key={item.id} className="space-y-2">
                <ul><Row item={item} siblings={roots} index={i} /></ul>
                {childrenOf(item.id).length > 0 && (
                  <ul className="space-y-2">
                    {childrenOf(item.id).map((c, j, arr) => <Row key={c.id} item={c} siblings={arr} index={j} child />)}
                  </ul>
                )}
              </li>
            ))}
          </ul>
        )}
      </Card>
      {editing && (
        <MenuItemModal
          item={editing === 'new' ? null : editing}
          location={location}
          parents={roots}
          onClose={() => setEditing(null)}
          onSaved={() => {
            setEditing(null)
            refresh()
          }}
        />
      )}
    </div>
  )
}
