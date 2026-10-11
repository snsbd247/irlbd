import { useEffect, useRef, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowLeft, Eye, Send, SendHorizontal } from 'lucide-react'
import { api, errorMessage, fieldErrors } from '../../lib/api'
import { formatDate } from '../../lib/utils'
import { ErrorState, Spinner } from '../../components/ui/States'
import { FieldGrid } from '../components/Fields'
import { Badge, Card, Modal, PageHeader, SaveButton, useConfirm, useToast } from '../components/ui'
import { useAuth } from '../auth'
import { NotAllowed } from './Misc'

const FIELDS = [
  { name: 'subject', label: 'Email subject', type: 'text', required: true, span: 'full', max: 200 },
  { name: 'preheader', label: 'Preview text (shown after the subject in most inboxes)', type: 'text', span: 'full', max: 200 },
  { name: 'content', label: 'Content', type: 'richtext', span: 'full', minHeight: 360 },
]

const sleep = (ms) => new Promise((r) => setTimeout(r, ms))

export default function NewsletterEditor() {
  const { id } = useParams()
  const isNew = id === 'new'
  const navigate = useNavigate()
  const toast = useToast()
  const confirm = useConfirm()
  const queryClient = useQueryClient()
  const { can, user } = useAuth()
  const [record, setRecord] = useState({ subject: '', preheader: '', content: '' })
  const [errors, setErrors] = useState({})
  const [saving, setSaving] = useState(false)
  const [preview, setPreview] = useState(null)
  const [testEmail, setTestEmail] = useState(user.email)
  const [sending, setSending] = useState(null) // { sent, failed, total }
  const stopRef = useRef(false)

  const { data, isLoading, isError, refetch } = useQuery({
    queryKey: ['admin', 'newsletter-campaigns', 'one', id],
    queryFn: () => api.get(`/admin/newsletter-campaigns/${id}`).then((r) => r.data),
    enabled: !isNew && can('subscribers'),
  })
  const { data: recipients } = useQuery({
    queryKey: ['admin', 'newsletter-recipients'],
    queryFn: () => api.get('/admin/newsletter-campaigns/recipients').then((r) => r.data),
    enabled: can('subscribers'),
  })

  useEffect(() => {
    if (data) setRecord(data)
  }, [data])
  useEffect(() => () => { stopRef.current = true }, [])

  if (!can('subscribers')) return <NotAllowed />
  if (!isNew && isLoading) return <div className="flex justify-center py-20"><Spinner /></div>
  if (!isNew && isError) return <ErrorState onRetry={refetch} />

  const locked = record.status && record.status !== 'draft'

  async function save(e) {
    e?.preventDefault()
    setSaving(true)
    setErrors({})
    try {
      const payload = { subject: record.subject, preheader: record.preheader, content: record.content }
      const res = isNew ? await api.post('/admin/newsletter-campaigns', payload) : await api.put(`/admin/newsletter-campaigns/${id}`, payload)
      toast('Campaign saved.')
      queryClient.invalidateQueries({ queryKey: ['admin', 'newsletter-campaigns'] })
      if (isNew) navigate(`/admin/newsletters/${res.data.id}`, { replace: true })
      else setRecord(res.data)
      return true
    } catch (err) {
      setErrors(fieldErrors(err))
      toast(errorMessage(err, 'Could not save the campaign.'), 'error')
      return false
    } finally {
      setSaving(false)
    }
  }

  async function showPreview() {
    try {
      const { data: res } = await api.get(`/admin/newsletter-campaigns/${id}/preview`)
      setPreview(res.html)
    } catch (err) {
      toast(errorMessage(err), 'error')
    }
  }

  async function sendTest() {
    if (!locked && !(await save())) return
    try {
      const { data: res } = await api.post(`/admin/newsletter-campaigns/${id}/test`, { email: testEmail })
      toast(res.message)
    } catch (err) {
      toast(fieldErrors(err).email || errorMessage(err, 'The test email could not be sent. Check the mail settings in .env.'), 'error')
    }
  }

  async function sendAll() {
    const total = recipients?.subscribed ?? 0
    if (!(await confirm(`Send "${record.subject}" to ${total} subscriber${total === 1 ? '' : 's'}? This cannot be undone. Keep this page open until sending finishes.`, { title: 'Send newsletter', confirmLabel: 'Send now', danger: false }))) return
    if (record.status === 'draft' && !(await save())) return
    stopRef.current = false
    setSending({ sent: record.sent_count || 0, failed: record.failed_count || 0, total: Math.max(total, record.recipients_total || 0) })
    try {
      // Each request sends one small batch (no cron/queue needed on shared hosting).
      for (;;) {
        if (stopRef.current) break
        const { data: res } = await api.post(`/admin/newsletter-campaigns/${id}/send-batch`)
        setRecord(res.campaign)
        setSending({ sent: res.campaign.sent_count, failed: res.campaign.failed_count, total: res.campaign.recipients_total })
        if (res.done) break
        await sleep(res.busy ? 3000 : 1500) // gentle pace for the host's mail limits
      }
      if (!stopRef.current) toast('Newsletter sent.')
    } catch (err) {
      toast(errorMessage(err, 'Sending stopped. You can resume it from this page.'), 'error')
    } finally {
      setSending(null)
      queryClient.invalidateQueries({ queryKey: ['admin', 'newsletter-campaigns'] })
    }
  }

  const total = sending?.total || record.recipients_total || 0
  const done = sending ? sending.sent + sending.failed : (record.sent_count || 0) + (record.failed_count || 0)
  const pct = total ? Math.round((done / total) * 100) : 0

  return (
    <div className="space-y-6">
      <form onSubmit={save} noValidate className="space-y-6">
        <PageHeader
          title={isNew ? 'New newsletter' : record.subject || 'Newsletter'}
          description={!isNew && (
            <span className="flex items-center gap-2">
              <Badge tone={{ draft: 'amber', sending: 'blue', sent: 'green' }[record.status]}>{record.status}</Badge>
              {record.finished_at && `sent ${formatDate(record.finished_at)}`}
            </span>
          )}
          actions={
            <>
              <Link to="/admin/r/newsletter-campaigns" className="btn-outline"><ArrowLeft className="h-4 w-4" aria-hidden="true" /> Campaigns</Link>
              {!locked && <SaveButton saving={saving}>Save draft</SaveButton>}
            </>
          }
        />
        {locked && <p className="rounded-md bg-slate-100 px-4 py-3 text-sm text-slate-700">This campaign has been {record.status === 'sent' ? 'sent' : 'started'} and can no longer be edited. Duplicate it by creating a new campaign.</p>}
        <Card>
          <fieldset disabled={locked} className="disabled:opacity-80">
            <FieldGrid fields={FIELDS} record={record} setRecord={setRecord} errors={errors} idPrefix="newsletter" />
          </fieldset>
        </Card>
      </form>

      {!isNew && (
        <Card title="Preview & send">
          <div className="grid gap-6 lg:grid-cols-2">
            <div className="space-y-3">
              <button type="button" className="btn-outline" onClick={showPreview}><Eye className="h-4 w-4" aria-hidden="true" /> Preview email</button>
              <div className="flex gap-2">
                <label htmlFor="newsletter-test-email" className="sr-only">Test email address</label>
                <input id="newsletter-test-email" type="email" className="input" value={testEmail} onChange={(e) => setTestEmail(e.target.value)} />
                <button type="button" className="btn-outline shrink-0" onClick={sendTest}><SendHorizontal className="h-4 w-4" aria-hidden="true" /> Send test</button>
              </div>
              <p className="text-xs text-muted">Always send a test to yourself first and check it on your phone.</p>
            </div>
            <div className="space-y-3">
              <p className="text-sm text-slate-700">
                Active subscribers: <strong className="text-navy-900">{recipients?.subscribed ?? '…'}</strong>
                {recipients && <span className="text-muted"> · sent in batches of {recipients.batch_size}</span>}
              </p>
              {(sending || record.status === 'sending' || record.status === 'sent') && (
                <div>
                  <div className="h-3 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuemin={0} aria-valuemax={100} aria-valuenow={pct} aria-label="Sending progress">
                    <div className="h-full bg-brand-600 transition-all" style={{ width: `${pct}%` }} />
                  </div>
                  <p className="mt-1 text-xs text-muted">{done} / {total} processed{(sending?.failed || record.failed_count) ? ` · ${sending?.failed ?? record.failed_count} failed` : ''}</p>
                </div>
              )}
              {record.status !== 'sent' && (
                sending ? (
                  <button type="button" className="btn-outline" onClick={() => { stopRef.current = true }}>
                    <Spinner className="h-4 w-4" /> Sending… (click to pause)
                  </button>
                ) : (
                  <button type="button" className="btn-primary" onClick={sendAll} disabled={!recipients?.subscribed}>
                    <Send className="h-4 w-4" aria-hidden="true" /> {record.status === 'sending' ? 'Resume sending' : 'Send to all subscribers'}
                  </button>
                )
              )}
              {sending && <p className="text-xs text-amber-700">Keep this page open until sending finishes.</p>}
            </div>
          </div>
        </Card>
      )}

      <Modal open={!!preview} onClose={() => setPreview(null)} title="Email preview" size="xl">
        {/* sandbox="" blocks scripts, forms and navigation inside the preview */}
        <iframe title="Newsletter preview" sandbox="" srcDoc={preview || ''} className="h-[70vh] w-full rounded-lg border border-slate-200" />
      </Modal>
    </div>
  )
}
