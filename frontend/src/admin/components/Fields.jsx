import { lazy, Suspense } from 'react'
import { useQuery } from '@tanstack/react-query'
import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-react'
import { api } from '../../lib/api'
import { Icon, ICONS } from '../../lib/icons'
import { cn } from '../../lib/utils'
import { Spinner } from '../../components/ui/States'
import { useMeta } from '../auth'
import { FileField, GalleryField, ImageField } from './Media'
import { Toggle } from './ui'
import ErrorBoundary from '../../components/ui/ErrorBoundary'

const RichTextEditor = lazy(() => import('./RichTextEditor'))

export function IconSelect({ id, value, onChange }) {
  return (
    <div className="flex items-center gap-2">
      <span className="icon-circle h-10 w-10 shrink-0 [&>svg]:h-5 [&>svg]:w-5">{value ? <Icon name={value} /> : null}</span>
      <select id={id} className="input" value={value || ''} onChange={(e) => onChange(e.target.value || null)}>
        <option value="">— No icon —</option>
        {Object.keys(ICONS).map((name) => (
          <option key={name} value={name}>{name}</option>
        ))}
      </select>
    </div>
  )
}

function RelationSelect({ id, field, value, onChange }) {
  const { data = [], isLoading } = useQuery({
    queryKey: ['admin', 'options', field.endpoint],
    queryFn: () => api.get(field.endpoint, { params: { all: 1 } }).then((r) => r.data.data ?? r.data),
    staleTime: 60 * 1000,
  })
  return (
    <select id={id} className="input" value={value ?? ''} onChange={(e) => onChange(e.target.value === '' ? null : Number(e.target.value))} disabled={isLoading}>
      <option value="">{field.placeholder || '— None —'}</option>
      {data.map((o) => (
        <option key={o.id} value={o.id}>{o[field.labelKey || 'name'] ?? o.title}</option>
      ))}
    </select>
  )
}

function CheckboxesField({ field, value = [], onChange }) {
  const { data = [], isLoading } = useQuery({
    queryKey: ['admin', 'options', field.endpoint],
    queryFn: () => api.get(field.endpoint, { params: { all: 1 } }).then((r) => r.data.data ?? r.data),
    staleTime: 60 * 1000,
  })
  if (isLoading) return <Spinner className="h-5 w-5" />
  if (!data.length) return <p className="text-sm text-muted">Nothing to choose yet.</p>
  return (
    <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
      {data.map((o) => (
        <label key={o.id} className="flex items-center gap-2 rounded-md border border-slate-200 p-2.5 text-sm hover:bg-slate-50">
          <input
            type="checkbox"
            className="h-4 w-4 accent-brand-600"
            checked={value.includes(o.id)}
            onChange={(e) => onChange(e.target.checked ? [...value, o.id] : value.filter((v) => v !== o.id))}
          />
          <span>{o[field.labelKey || 'name']}</span>
        </label>
      ))}
    </div>
  )
}

function PermissionsField({ value = [], onChange }) {
  const { data: meta } = useMeta()
  const perms = meta?.permissions || []
  return (
    <div className="grid gap-2 sm:grid-cols-2">
      {perms.map((p) => (
        <label key={p.slug} className="flex items-start gap-2 rounded-md border border-slate-200 p-2.5 text-sm hover:bg-slate-50">
          <input
            type="checkbox"
            className="mt-0.5 h-4 w-4 accent-brand-600"
            checked={value.includes(p.slug)}
            onChange={(e) => onChange(e.target.checked ? [...value, p.slug] : value.filter((v) => v !== p.slug))}
          />
          <span>{p.label}</span>
        </label>
      ))}
    </div>
  )
}

function Repeater({ field, value = [], onChange, errors, name }) {
  const update = (i, key, v) => onChange(value.map((row, j) => (j === i ? { ...row, [key]: v } : row)))
  const move = (i, dir) => {
    const next = [...value]
    const [row] = next.splice(i, 1)
    next.splice(i + dir, 0, row)
    onChange(next)
  }
  return (
    <div className="space-y-2">
      {value.map((row, i) => (
        <div key={row.id ?? `new-${i}`} className="flex flex-col gap-2 rounded-lg border border-slate-200 bg-slate-50/50 p-3 sm:flex-row sm:items-start">
          <div className={cn('grid flex-1 gap-2', field.fields.length > 1 && 'sm:grid-cols-2')}>
            {field.fields.map((sub) => (
              <FieldControl
                key={sub.name}
                field={{ ...sub, label: sub.label }}
                value={row[sub.name]}
                record={row}
                onChange={(v, extra) => {
                  update(i, sub.name, v)
                  if (extra) Object.entries(extra).forEach(([k, val]) => update(i, k, val))
                }}
                error={errors?.[`${name}.${i}.${sub.name}`]}
                idPrefix={`${name}-${i}`}
                compact
              />
            ))}
            {field.fields.filter((sub) => sub.t).map((sub) => (
              <div key={`bn-${sub.name}`}>
                <label htmlFor={`${name}-${i}-bn-${sub.name}`} className="label text-xs">{sub.label} <span lang="bn" className="text-brand-600">(বাংলা)</span></label>
                <input
                  id={`${name}-${i}-bn-${sub.name}`}
                  lang="bn"
                  className="input"
                  value={row.translations?.bn?.[sub.name] ?? ''}
                  placeholder={row[sub.name] || ''}
                  onChange={(e) => update(i, 'translations', withTranslation(row.translations, 'bn', sub.name, e.target.value))}
                />
              </div>
            ))}
          </div>
          <div className="flex gap-1 sm:flex-col">
            <button type="button" className="rounded p-1.5 hover:bg-slate-200 disabled:opacity-30" onClick={() => move(i, -1)} disabled={i === 0} aria-label="Move up"><ArrowUp className="h-4 w-4" /></button>
            <button type="button" className="rounded p-1.5 hover:bg-slate-200 disabled:opacity-30" onClick={() => move(i, 1)} disabled={i === value.length - 1} aria-label="Move down"><ArrowDown className="h-4 w-4" /></button>
            <button type="button" className="rounded p-1.5 text-red-600 hover:bg-red-50" onClick={() => onChange(value.filter((_, j) => j !== i))} aria-label="Remove row"><Trash2 className="h-4 w-4" /></button>
          </div>
        </div>
      ))}
      <button type="button" className="btn-outline py-2" onClick={() => onChange([...value, { ...(field.defaults || {}) }])}>
        <Plus className="h-4 w-4" aria-hidden="true" /> {field.addLabel || 'Add row'}
      </button>
    </div>
  )
}

/** Renders one form control based on its schema definition. */
export function FieldControl({ field, value, onChange, record = {}, error, idPrefix = 'f', compact = false }) {
  const id = `${idPrefix}-${field.name}`
  const common = { id, 'aria-invalid': !!error, 'aria-describedby': error ? `${id}-error` : undefined }
  let control

  switch (field.type) {
    case 'textarea':
      control = <textarea {...common} rows={field.rows || 3} className="input" value={value ?? ''} onChange={(e) => onChange(e.target.value)} maxLength={field.max} placeholder={field.placeholder} />
      break
    case 'richtext':
      control = (
        <ErrorBoundary>
          <Suspense fallback={<div className="flex h-40 items-center justify-center rounded-md border"><Spinner /></div>}>
            <RichTextEditor id={id} value={value} onChange={onChange} minHeight={field.minHeight} />
          </Suspense>
        </ErrorBoundary>
      )
      break
    case 'image':
      control = <ImageField id={id} value={value} url={record[field.urlKey || `${field.name}_url`]} onChange={(path) => onChange(path)} folder={field.folder} help={compact ? undefined : field.help} />
      break
    case 'file':
      control = <FileField id={id} value={value} url={record[field.urlKey || `${field.name}_url`]} onChange={(path) => onChange(path)} folder={field.folder || 'documents'} />
      break
    case 'gallery':
      control = <GalleryField value={value || []} onChange={onChange} folder={field.folder} bengali={field.bengali} />
      break
    case 'icon':
      control = <IconSelect id={id} value={value} onChange={onChange} />
      break
    case 'select':
      control = (
        <select {...common} className="input" value={value ?? ''} onChange={(e) => onChange(e.target.value === '' ? null : e.target.value)}>
          {!field.required && <option value="">{field.placeholder || '— Select —'}</option>}
          {field.options.map((o) => {
            const [val, label] = Array.isArray(o) ? o : [o, o]
            return <option key={val} value={val}>{label}</option>
          })}
        </select>
      )
      break
    case 'relation':
      control = <RelationSelect id={id} field={field} value={value} onChange={onChange} />
      break
    case 'toggle':
      control = <Toggle id={id} checked={value} onChange={onChange} label={field.toggleLabel || field.label} />
      break
    case 'number':
      control = <input {...common} type="number" className="input" value={value ?? ''} min={field.min} max={field.max} onChange={(e) => onChange(e.target.value === '' ? null : Number(e.target.value))} />
      break
    case 'date':
      control = <input {...common} type="date" className="input" value={(value || '').slice(0, 10)} onChange={(e) => onChange(e.target.value || null)} />
      break
    case 'datetime':
      control = (
        <input
          {...common}
          type="datetime-local"
          className="input"
          value={value ? toLocalInput(value) : ''}
          onChange={(e) => onChange(e.target.value ? new Date(e.target.value).toISOString() : null)}
        />
      )
      break
    case 'repeater':
      control = <Repeater field={field} value={value || []} onChange={onChange} errors={field.errors} name={field.name} />
      break
    case 'checkboxes':
      control = <CheckboxesField field={field} value={value || []} onChange={onChange} />
      break
    case 'permissions':
      control = <PermissionsField value={value || []} onChange={onChange} />
      break
    default:
      control = (
        <input
          {...common}
          type={field.type === 'email' || field.type === 'password' || field.type === 'url' ? field.type : 'text'}
          className="input"
          value={value ?? ''}
          onChange={(e) => onChange(e.target.value)}
          maxLength={field.max}
          placeholder={field.placeholder}
          autoComplete={field.type === 'password' ? 'new-password' : 'off'}
        />
      )
  }

  return (
    <div className={cn(field.span === 'full' && 'sm:col-span-2')}>
      {field.type !== 'toggle' && (
        <label htmlFor={id} className={cn('label', compact && 'text-xs')}>
          {field.label} {field.required && <span className="text-red-500">*</span>}
        </label>
      )}
      {control}
      {error && <p id={`${id}-error`} className="mt-1 text-xs text-red-600">{error}</p>}
      {!compact && field.help && field.type !== 'image' && <p className="mt-1 text-xs text-muted">{field.help}</p>}
    </div>
  )
}

function toLocalInput(iso) {
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return ''
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

/** A grid of fields bound to a record object. */
export function FieldGrid({ fields, record, setRecord, errors = {}, idPrefix = 'f' }) {
  return (
    <div className="grid gap-5 sm:grid-cols-2">
      {fields
        .filter((f) => !f.showIf || f.showIf(record))
        .map((field) => (
          <FieldControl
            key={field.name}
            field={field.type === 'repeater' ? { ...field, errors } : field}
            value={record[field.name]}
            record={record}
            onChange={(v) => setRecord((r) => ({ ...r, [field.name]: v }))}
            error={errors[field.name]}
            idPrefix={idPrefix}
          />
        ))}
    </div>
  )
}

/** Returns a copy of a `{bn: {field: value}}` translations object with one value changed. */
export function withTranslation(translations, locale, name, value) {
  const all = translations && !Array.isArray(translations) ? translations : {}
  return { ...all, [locale]: { ...(all[locale] || {}), [name]: value } }
}

/**
 * Bengali versions of a record's text fields. Empty fields fall back to English on
 * the website, so translations can be added gradually.
 */
export function TranslationCard({ fields, names, translations, onChange, english = {}, idPrefix = 't' }) {
  const shown = fields.filter((f) => names.includes(f.name) && ['text', 'textarea', 'richtext', undefined].includes(f.type))
  if (!shown.length) return null
  const bn = (translations && !Array.isArray(translations) && translations.bn) || {}
  return (
    <div className="grid gap-5 sm:grid-cols-2" lang="bn">
      {shown.map((f) => (
        <FieldControl
          key={f.name}
          field={{ ...f, required: false, help: undefined, label: `${f.label} (বাংলা)`, placeholder: f.type === 'richtext' ? undefined : english[f.name] || '' }}
          value={bn[f.name] ?? ''}
          onChange={(v) => onChange(withTranslation(translations, 'bn', f.name, v))}
          idPrefix={`${idPrefix}-bn`}
        />
      ))}
    </div>
  )
}
