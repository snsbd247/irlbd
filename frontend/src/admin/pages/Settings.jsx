import { useEffect, useMemo, useState } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { RefreshCw } from 'lucide-react'
import { api, errorMessage, fieldErrors } from '../../lib/api'
import { cn } from '../../lib/utils'
import { ErrorState, Spinner } from '../../components/ui/States'
import { FieldGrid } from '../components/Fields'
import { Card, PageHeader, SaveButton, useToast } from '../components/ui'
import { useAuth, useMeta } from '../auth'
import { NotAllowed } from './Misc'

const GROUPS = [
  ['branding', 'Branding', 'Site name, logos and favicon.'],
  ['contact', 'Contact', 'Phone, email and address shown across the site.'],
  ['social', 'Social', 'Social media profile links (leave empty to hide).'],
  ['header', 'Header', 'Utility bar and header button.'],
  ['footer', 'Footer', 'Footer text, copyright and newsletter box. Use {year} for the current year.'],
  ['cta', 'Call to action', 'Defaults for CTA banners when a section leaves fields empty.'],
  ['seo', 'SEO', 'Default metadata used when a page has none of its own.'],
  ['map', 'Map', 'Paste the src URL from Google Maps → Share → Embed a map (not the whole <iframe> code).'],
]

const TYPE_MAP = { text: 'text', textarea: 'textarea', email: 'email', url: 'text', image: 'image', boolean: 'toggle', map: 'textarea' }

export default function Settings() {
  const { can } = useAuth()
  const { data: meta } = useMeta()
  const toast = useToast()
  const queryClient = useQueryClient()
  const [tab, setTab] = useState('branding')
  const [values, setValues] = useState({})
  const [errors, setErrors] = useState({})
  const [saving, setSaving] = useState(false)
  const [sitemapBusy, setSitemapBusy] = useState(false)

  const { data, isLoading, isError, refetch } = useQuery({
    queryKey: ['admin', 'settings'],
    queryFn: () => api.get('/admin/settings').then((r) => r.data),
    enabled: can('settings'),
  })

  useEffect(() => {
    if (data) {
      const withUrls = { ...data.values }
      Object.entries(data.image_urls || {}).forEach(([k, url]) => (withUrls[`${k}_url`] = url))
      setValues(withUrls)
    }
  }, [data])

  const fieldsByGroup = useMemo(() => {
    const out = {}
    ;(meta?.settings || []).forEach((s) => {
      out[s.group] ??= []
      out[s.group].push({
        name: s.key,
        label: s.label + (s.public ? '' : ' 🔒'),
        type: TYPE_MAP[s.type] || 'text',
        folder: 'branding',
        span: ['textarea', 'image', 'map'].includes(s.type) || s.key.includes('url') ? 'full' : undefined,
      })
    })
    return out
  }, [meta])

  if (!can('settings')) return <NotAllowed />
  if (isLoading || !meta) return <div className="flex justify-center py-20"><Spinner /></div>
  if (isError) return <ErrorState onRetry={refetch} />

  async function save(e) {
    e.preventDefault()
    setSaving(true)
    setErrors({})
    const payload = Object.fromEntries(meta.settings.map((s) => [s.key, s.type === 'boolean' ? !!values[s.key] : values[s.key] ?? null]))
    try {
      await api.put('/admin/settings', payload)
      toast('Settings saved. Changes are live on the website.')
      queryClient.invalidateQueries({ queryKey: ['admin', 'settings'] })
      queryClient.invalidateQueries({ queryKey: ['settings'] })
    } catch (err) {
      const errs = fieldErrors(err)
      setErrors(errs)
      const first = meta.settings.find((s) => errs[s.key])
      if (first) setTab(first.group)
      toast(errorMessage(err, 'Could not save settings.'), 'error')
    }
    setSaving(false)
  }

  async function regenerateSitemap() {
    setSitemapBusy(true)
    try {
      const { data: res } = await api.post('/admin/sitemap')
      toast(res.message, res.written ? 'success' : 'error')
    } catch (err) {
      toast(errorMessage(err), 'error')
    }
    setSitemapBusy(false)
  }

  const group = GROUPS.find(([k]) => k === tab)

  return (
    <form onSubmit={save} noValidate>
      <PageHeader title="General settings" description="Branding, contact details and site-wide defaults. Fields marked 🔒 are never shown publicly." actions={<SaveButton saving={saving} />} />
      <div className="grid gap-6 lg:grid-cols-[220px_1fr]">
        <nav aria-label="Settings sections" className="flex gap-1 overflow-x-auto lg:flex-col">
          {GROUPS.map(([k, label]) => (
            <button
              key={k}
              type="button"
              onClick={() => setTab(k)}
              aria-current={tab === k}
              className={cn(
                'rounded-md px-3 py-2 text-left text-sm font-medium whitespace-nowrap',
                tab === k ? 'bg-brand-600 text-white' : 'text-slate-700 hover:bg-white',
                meta.settings.some((s) => s.group === k && errors[s.key]) && tab !== k && 'text-red-600',
              )}
            >
              {label}
            </button>
          ))}
        </nav>
        <Card title={group[1]}>
          <p className="mb-5 text-sm text-muted">{group[2]}</p>
          <FieldGrid fields={fieldsByGroup[tab] || []} record={values} setRecord={setValues} errors={errors} idPrefix={`settings-${tab}`} />
          {tab === 'seo' && (
            <div className="mt-6 rounded-lg border border-slate-200 p-4">
              <p className="text-sm font-medium text-navy-900">Sitemap</p>
              <p className="mt-1 text-sm text-muted">
                The live sitemap is always available at <code className="rounded bg-slate-100 px-1">/api/v1/sitemap.xml</code>. If SITEMAP_OUTPUT_PATH is configured, you can also write a static sitemap.xml to the website root.
              </p>
              <button type="button" className="btn-outline mt-3" onClick={regenerateSitemap} disabled={sitemapBusy}>
                {sitemapBusy ? <Spinner className="h-4 w-4" /> : <RefreshCw className="h-4 w-4" aria-hidden="true" />} Regenerate sitemap.xml
              </button>
            </div>
          )}
        </Card>
      </div>
    </form>
  )
}
