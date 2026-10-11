import { FileDown } from 'lucide-react'
import { useSettings } from '../../lib/queries'
import { cn } from '../../lib/utils'

/** "Download Brochure" — shown only when a brochure PDF is set in General Settings › Downloads. */
export default function BrochureButton({ className = 'btn-outline', iconClass = 'h-4 w-4' }) {
  const { data: s = {} } = useSettings()
  if (!s.brochure_file_url) return null
  return (
    <a href={s.brochure_file_url} target="_blank" rel="noopener noreferrer" className={cn(className)}>
      <FileDown className={iconClass} aria-hidden="true" /> {s.brochure_label || 'Download Brochure'}
    </a>
  )
}
