import { useRef, useState } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowDown, ArrowUp, FileText, ImagePlus, Images, Search, Trash2, Upload, X } from 'lucide-react'
import { api, errorMessage } from '../../lib/api'
import { cn } from '../../lib/utils'
import { EmptyState, ErrorState, Spinner } from '../../components/ui/States'
import Pagination from '../../components/ui/Pagination'
import { Modal, useToast } from './ui'

const IMAGE_ACCEPT = 'image/jpeg,image/png,image/webp,image/gif,image/x-icon,image/vnd.microsoft.icon,.ico'
const DOC_ACCEPT = 'application/pdf,.pdf'
const isPdf = (m) => m?.mime_type === 'application/pdf' || /\.pdf$/i.test(m?.path || '')

export function useUpload(folder = 'general') {
  const queryClient = useQueryClient()
  const toast = useToast()
  const [uploading, setUploading] = useState(false)

  async function upload(files) {
    setUploading(true)
    const uploaded = []
    for (const file of files) {
      const body = new FormData()
      body.append('file', file)
      body.append('folder', folder)
      try {
        const { data } = await api.post('/admin/media', body, { headers: { 'Content-Type': 'multipart/form-data' } })
        uploaded.push(data)
      } catch (error) {
        toast(`${file.name}: ${error.response?.data?.errors?.file?.[0] || errorMessage(error, 'Upload failed.')}`, 'error')
      }
    }
    setUploading(false)
    if (uploaded.length) {
      toast(`${uploaded.length} file${uploaded.length > 1 ? 's' : ''} uploaded.`)
      queryClient.invalidateQueries({ queryKey: ['admin', 'media'] })
    }
    return uploaded
  }

  return { upload, uploading }
}

export function UploadButton({ folder, onUploaded, multiple = true, className = 'btn-primary', accept = `${IMAGE_ACCEPT},${DOC_ACCEPT}` }) {
  const input = useRef(null)
  const { upload, uploading } = useUpload(folder)
  return (
    <>
      <button type="button" className={className} onClick={() => input.current?.click()} disabled={uploading}>
        {uploading ? <Spinner className="h-4 w-4 text-current" label="Uploading" /> : <Upload className="h-4 w-4" aria-hidden="true" />}
        {uploading ? 'Uploading…' : 'Upload'}
      </button>
      <input
        ref={input}
        type="file"
        accept={accept}
        multiple={multiple}
        className="hidden"
        onChange={async (e) => {
          const files = [...e.target.files]
          e.target.value = ''
          if (!files.length) return
          // Upload first; `onUploaded?.(await upload())` would skip the upload entirely when no callback is passed.
          const uploaded = await upload(files)
          onUploaded?.(uploaded)
        }}
      />
    </>
  )
}

/** Paginated media grid used by the Media Library page and the picker. */
/** `uploadFolder` is where new uploads go; the grid itself shows every folder unless filtered. */
export function MediaGrid({ onSelect, selectedPath, folder: uploadFolder = 'general', type }) {
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')
  const [folder, setFolder] = useState('')
  const { data, isLoading, isError, refetch } = useQuery({
    queryKey: ['admin', 'media', { page, search, folder, type }],
    queryFn: () => api.get('/admin/media', { params: { page, search: search || undefined, folder: folder || undefined, type, per_page: 24 } }).then((r) => r.data),
    placeholderData: (prev) => prev,
  })

  return (
    <div>
      <div className="mb-4 flex flex-col gap-2 sm:flex-row">
        <div className="relative flex-1">
          <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
          <input type="search" aria-label="Search media" className="input pl-9" placeholder="Search by file name…" value={search} onChange={(e) => { setSearch(e.target.value); setPage(1) }} />
        </div>
        <select aria-label="Folder" className="input sm:w-44" value={folder} onChange={(e) => { setFolder(e.target.value); setPage(1) }}>
          <option value="">All folders</option>
          {['general', 'branding', 'pages', 'products', 'news', 'team', 'clients', 'certifications', 'history', 'slides', 'gallery', 'documents'].map((f) => (
            <option key={f} value={f}>{f}</option>
          ))}
        </select>
        <UploadButton folder={folder || uploadFolder} accept={type === 'document' ? DOC_ACCEPT : type === 'image' ? IMAGE_ACCEPT : undefined} onUploaded={(files) => files[0] && onSelect?.(files[0], { uploaded: true })} />
      </div>
      {isLoading ? (
        <div className="flex justify-center py-12"><Spinner /></div>
      ) : isError ? (
        <ErrorState onRetry={refetch} />
      ) : !data?.data?.length ? (
        <EmptyState icon={Images} title={type === 'document' ? 'No documents yet' : 'No files yet'} message={type === 'document' ? 'Upload PDF documents (max 15 MB).' : 'Upload JPG, PNG, WebP, GIF or ICO images (max 5 MB) or PDF documents.'} />
      ) : (
        <>
          <ul className="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-6">
            {data.data.map((m) => (
              <li key={m.id}>
                <button
                  type="button"
                  onClick={() => onSelect?.(m)}
                  className={cn('group block w-full overflow-hidden rounded-lg border-2 bg-slate-50 text-left', selectedPath === m.path ? 'border-brand-600' : 'border-transparent hover:border-brand-200')}
                  title={m.original_name}
                >
                  <span className="block aspect-square bg-[repeating-conic-gradient(#f1f5f9_0_25%,#fff_0_50%)] bg-[length:16px_16px]">
                    {isPdf(m) ? (
                      <span className="flex h-full w-full flex-col items-center justify-center gap-1 bg-red-50 text-red-600">
                        <FileText className="h-10 w-10" aria-hidden="true" />
                        <span className="text-xs font-bold">PDF</span>
                      </span>
                    ) : (
                      <img src={m.url} alt={m.alt || m.original_name} loading="lazy" className="h-full w-full object-contain" />
                    )}
                  </span>
                  <span className="block truncate px-2 py-1 text-[11px] text-slate-600">{m.original_name}</span>
                </button>
              </li>
            ))}
          </ul>
          <Pagination meta={data.meta} onChange={setPage} className="mt-5" />
        </>
      )}
    </div>
  )
}

export function MediaPickerModal({ open, onClose, onPick, folder, selectedPath, type = 'image', title = 'Choose an image' }) {
  return (
    <Modal open={open} onClose={onClose} title={title} size="xl">
      <MediaGrid
        folder={folder}
        type={type}
        selectedPath={selectedPath}
        onSelect={(media) => {
          onPick(media)
          onClose()
        }}
      />
    </Modal>
  )
}

/** Single image field storing a media-library path; shows a preview. */
export function ImageField({ id, value, url, onChange, folder = 'general', help }) {
  const [open, setOpen] = useState(false)
  const [preview, setPreview] = useState(url)
  const shown = value ? preview || url : null

  return (
    <div>
      <div className="flex items-center gap-4">
        <div className="flex h-24 w-32 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-[repeating-conic-gradient(#f1f5f9_0_25%,#fff_0_50%)] bg-[length:16px_16px]">
          {shown ? <img src={shown} alt="Selected" className="h-full w-full object-contain" /> : <ImagePlus className="h-7 w-7 text-slate-300" aria-hidden="true" />}
        </div>
        <div className="flex flex-col gap-2">
          <button id={id} type="button" className="btn-outline py-2" onClick={() => setOpen(true)}>
            <Images className="h-4 w-4" aria-hidden="true" /> {value ? 'Change image' : 'Choose image'}
          </button>
          {value && (
            <button type="button" className="text-left text-xs text-red-600 hover:underline" onClick={() => { onChange(null); setPreview(null) }}>
              Remove image
            </button>
          )}
        </div>
      </div>
      {help && <p className="mt-1.5 text-xs text-muted">{help}</p>}
      <MediaPickerModal
        open={open}
        onClose={() => setOpen(false)}
        folder={folder}
        selectedPath={value}
        onPick={(m) => {
          onChange(m.path)
          setPreview(m.url)
        }}
      />
    </div>
  )
}

/** Single PDF document field storing a media-library path. */
export function FileField({ id, value, url, onChange, folder = 'documents' }) {
  const [open, setOpen] = useState(false)
  const [picked, setPicked] = useState(null)
  const shownUrl = value ? picked?.url || url : null
  const name = value ? picked?.original_name || value.split('/').pop() : null
  return (
    <div className="flex flex-wrap items-center gap-3">
      <span className="flex h-14 w-14 items-center justify-center rounded-lg border border-slate-200 bg-red-50 text-red-600">
        <FileText className="h-6 w-6" aria-hidden="true" />
      </span>
      <div className="min-w-0 flex-1">
        {value ? (
          <a href={shownUrl} target="_blank" rel="noopener noreferrer" className="block truncate text-sm font-medium text-brand-700 hover:underline">{name}</a>
        ) : (
          <p className="text-sm text-muted">No document selected</p>
        )}
        <div className="mt-1 flex gap-3">
          <button id={id} type="button" className="text-sm font-medium text-brand-600 hover:underline" onClick={() => setOpen(true)}>
            {value ? 'Change PDF' : 'Choose PDF'}
          </button>
          {value && (
            <button type="button" className="text-sm text-red-600 hover:underline" onClick={() => { onChange(null); setPicked(null) }}>Remove</button>
          )}
        </div>
      </div>
      <MediaPickerModal open={open} onClose={() => setOpen(false)} folder={folder} type="document" title="Choose a PDF document" selectedPath={value} onPick={(m) => { onChange(m.path); setPicked(m) }} />
    </div>
  )
}

/** Ordered gallery: [{ path, url, alt }] */
export function GalleryField({ value = [], onChange, folder = 'products' }) {
  const [open, setOpen] = useState(false)
  const move = (i, dir) => {
    const next = [...value]
    const [item] = next.splice(i, 1)
    next.splice(i + dir, 0, item)
    onChange(next)
  }
  return (
    <div>
      <ul className="grid grid-cols-2 gap-3 sm:grid-cols-4">
        {value.map((img, i) => (
          <li key={img.path + i} className="overflow-hidden rounded-lg border border-slate-200">
            <img src={img.url} alt={img.alt || ''} className="aspect-square w-full bg-slate-50 object-contain" />
            <div className="space-y-1.5 p-2">
              <input aria-label={`Alt text for image ${i + 1}`} className="input py-1 text-xs" placeholder="Alt text" value={img.alt || ''} onChange={(e) => onChange(value.map((v, j) => (j === i ? { ...v, alt: e.target.value } : v)))} />
              <div className="flex justify-between">
                <div className="flex gap-1">
                  <button type="button" className="rounded p-1 hover:bg-slate-100 disabled:opacity-30" disabled={i === 0} onClick={() => move(i, -1)} aria-label="Move left"><ArrowUp className="h-3.5 w-3.5 -rotate-90" aria-hidden="true" /></button>
                  <button type="button" className="rounded p-1 hover:bg-slate-100 disabled:opacity-30" disabled={i === value.length - 1} onClick={() => move(i, 1)} aria-label="Move right"><ArrowDown className="h-3.5 w-3.5 -rotate-90" aria-hidden="true" /></button>
                </div>
                <button type="button" className="rounded p-1 text-red-600 hover:bg-red-50" onClick={() => onChange(value.filter((_, j) => j !== i))} aria-label="Remove image"><Trash2 className="h-3.5 w-3.5" aria-hidden="true" /></button>
              </div>
            </div>
          </li>
        ))}
        <li>
          <button type="button" onClick={() => setOpen(true)} className="flex aspect-square w-full flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-slate-300 text-sm text-slate-500 hover:border-brand-600 hover:text-brand-600">
            <ImagePlus className="h-6 w-6" aria-hidden="true" /> Add image
          </button>
        </li>
      </ul>
      <MediaPickerModal open={open} onClose={() => setOpen(false)} folder={folder} onPick={(m) => onChange([...value, { path: m.path, url: m.url, alt: m.alt || '' }])} />
    </div>
  )
}

export function MediaDetails({ media, onClose, onDeleted }) {
  const toast = useToast()
  const queryClient = useQueryClient()
  const [alt, setAlt] = useState(media.alt || '')
  const [busy, setBusy] = useState(false)

  async function save() {
    setBusy(true)
    try {
      await api.put(`/admin/media/${media.id}`, { alt })
      toast('Alt text saved.')
      queryClient.invalidateQueries({ queryKey: ['admin', 'media'] })
    } catch (e) {
      toast(errorMessage(e), 'error')
    }
    setBusy(false)
  }

  return (
    <div className="grid gap-5 md:grid-cols-2">
      {isPdf(media) ? (
        <a href={media.url} target="_blank" rel="noopener noreferrer" className="flex h-64 flex-col items-center justify-center gap-2 rounded-lg border border-slate-200 bg-red-50 text-red-600 hover:bg-red-100">
          <FileText className="h-14 w-14" aria-hidden="true" />
          <span className="text-sm font-semibold">Open PDF</span>
        </a>
      ) : (
        <img src={media.url} alt={media.alt || ''} className="max-h-80 w-full rounded-lg border border-slate-200 bg-slate-50 object-contain" />
      )}
      <div className="space-y-3 text-sm">
        <p className="font-medium break-all text-navy-900">{media.original_name}</p>
        <p className="text-muted">
          {media.mime_type} · {(media.size / 1024).toFixed(0)} KB {media.width ? `· ${media.width}×${media.height}px` : ''}
        </p>
        <div>
          <label htmlFor="media-url" className="label">URL</label>
          <div className="flex gap-2">
            <input id="media-url" className="input text-xs" readOnly value={media.url} onFocus={(e) => e.target.select()} />
            <button type="button" className="btn-outline px-3 py-2" onClick={() => navigator.clipboard?.writeText(media.url).then(() => toast('URL copied.'))}>Copy</button>
          </div>
        </div>
        <div>
          <label htmlFor="media-alt" className="label">Alt text (accessibility & SEO)</label>
          <input id="media-alt" className="input" value={alt} onChange={(e) => setAlt(e.target.value)} maxLength={255} />
        </div>
        <div className="flex justify-between gap-2 pt-2">
          <button type="button" className="btn bg-red-50 text-red-700 hover:bg-red-100" onClick={() => onDeleted(media)}>
            <Trash2 className="h-4 w-4" aria-hidden="true" /> Delete
          </button>
          <div className="flex gap-2">
            <button type="button" className="btn-outline" onClick={onClose}><X className="h-4 w-4" aria-hidden="true" /> Close</button>
            <button type="button" className="btn-primary" onClick={save} disabled={busy}>Save</button>
          </div>
        </div>
      </div>
    </div>
  )
}
