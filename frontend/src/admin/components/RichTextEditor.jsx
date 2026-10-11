import { useState } from 'react'
import { EditorContent, useEditor, useEditorState } from '@tiptap/react'
import StarterKit from '@tiptap/starter-kit'
import Image from '@tiptap/extension-image'
import {
  Bold, Heading2, Heading3, ImagePlus, Italic, Link as LinkIcon, List, ListOrdered, Minus, Redo2,
  RemoveFormatting, Strikethrough, TextQuote, Underline as UnderlineIcon, Undo2, Unlink,
} from 'lucide-react'
import { cn } from '../../lib/utils'
import { MediaPickerModal } from './Media'

function ToolButton({ onClick, active, label, children, disabled }) {
  return (
    <button
      type="button"
      onMouseDown={(e) => e.preventDefault()}
      onClick={onClick}
      disabled={disabled}
      aria-label={label}
      title={label}
      aria-pressed={active}
      className={cn('rounded p-1.5 text-slate-600 hover:bg-slate-200 disabled:opacity-30', active && 'bg-brand-100 text-brand-700')}
    >
      {children}
    </button>
  )
}

/**
 * WYSIWYG editor producing plain semantic HTML. The server re-sanitizes everything
 * against a strict allow-list, so pasted scripts/styles are always stripped.
 */
export default function RichTextEditor({ id, value, onChange, minHeight = 220 }) {
  const [pickerOpen, setPickerOpen] = useState(false)
  const editor = useEditor({
    extensions: [
      StarterKit.configure({
        heading: { levels: [2, 3, 4] },
        link: { openOnClick: false, autolink: true, protocols: ['http', 'https', 'mailto', 'tel'], HTMLAttributes: { rel: 'noopener noreferrer' } },
      }),
      Image.configure({ inline: false }),
    ],
    content: value || '',
    editorProps: {
      attributes: { id, class: 'rich-text max-w-none px-4 py-3 outline-none', style: `min-height:${minHeight}px` },
    },
    onUpdate: ({ editor: e }) => onChange(e.isEmpty ? '' : e.getHTML()),
  })

  const state = useEditorState({
    editor,
    selector: ({ editor: e }) =>
      e
        ? {
            bold: e.isActive('bold'),
            italic: e.isActive('italic'),
            underline: e.isActive('underline'),
            strike: e.isActive('strike'),
            h2: e.isActive('heading', { level: 2 }),
            h3: e.isActive('heading', { level: 3 }),
            bullet: e.isActive('bulletList'),
            ordered: e.isActive('orderedList'),
            quote: e.isActive('blockquote'),
            link: e.isActive('link'),
            canUndo: e.can().undo(),
            canRedo: e.can().redo(),
          }
        : {},
  })

  if (!editor) return null
  const chain = () => editor.chain().focus()

  function setLink() {
    const previous = editor.getAttributes('link').href || ''
    const url = window.prompt('Link URL (https://…, /page, mailto:…)', previous)
    if (url === null) return
    if (url === '') return chain().unsetLink().run()
    if (!/^(https?:\/\/|\/|#|mailto:|tel:)/i.test(url)) {
      window.alert('Links must start with https://, /, #, mailto: or tel:')
      return
    }
    chain().extendMarkRange('link').setLink({ href: url, target: /^https?:/i.test(url) ? '_blank' : null }).run()
  }

  return (
    <div className="overflow-hidden rounded-md border border-slate-300 bg-white focus-within:border-brand-600 focus-within:ring-2 focus-within:ring-brand-600/20">
      <div className="flex flex-wrap gap-0.5 border-b border-slate-200 bg-slate-50 px-2 py-1.5" role="toolbar" aria-label="Formatting">
        <ToolButton label="Heading 2" active={state.h2} onClick={() => chain().toggleHeading({ level: 2 }).run()}><Heading2 className="h-4 w-4" /></ToolButton>
        <ToolButton label="Heading 3" active={state.h3} onClick={() => chain().toggleHeading({ level: 3 }).run()}><Heading3 className="h-4 w-4" /></ToolButton>
        <span className="mx-1 w-px bg-slate-200" />
        <ToolButton label="Bold" active={state.bold} onClick={() => chain().toggleBold().run()}><Bold className="h-4 w-4" /></ToolButton>
        <ToolButton label="Italic" active={state.italic} onClick={() => chain().toggleItalic().run()}><Italic className="h-4 w-4" /></ToolButton>
        <ToolButton label="Underline" active={state.underline} onClick={() => chain().toggleUnderline().run()}><UnderlineIcon className="h-4 w-4" /></ToolButton>
        <ToolButton label="Strikethrough" active={state.strike} onClick={() => chain().toggleStrike().run()}><Strikethrough className="h-4 w-4" /></ToolButton>
        <span className="mx-1 w-px bg-slate-200" />
        <ToolButton label="Bullet list" active={state.bullet} onClick={() => chain().toggleBulletList().run()}><List className="h-4 w-4" /></ToolButton>
        <ToolButton label="Numbered list" active={state.ordered} onClick={() => chain().toggleOrderedList().run()}><ListOrdered className="h-4 w-4" /></ToolButton>
        <ToolButton label="Quote" active={state.quote} onClick={() => chain().toggleBlockquote().run()}><TextQuote className="h-4 w-4" /></ToolButton>
        <ToolButton label="Divider" onClick={() => chain().setHorizontalRule().run()}><Minus className="h-4 w-4" /></ToolButton>
        <span className="mx-1 w-px bg-slate-200" />
        <ToolButton label="Add link" active={state.link} onClick={setLink}><LinkIcon className="h-4 w-4" /></ToolButton>
        <ToolButton label="Remove link" disabled={!state.link} onClick={() => chain().unsetLink().run()}><Unlink className="h-4 w-4" /></ToolButton>
        <ToolButton label="Insert image" onClick={() => setPickerOpen(true)}><ImagePlus className="h-4 w-4" /></ToolButton>
        <ToolButton label="Clear formatting" onClick={() => chain().unsetAllMarks().clearNodes().run()}><RemoveFormatting className="h-4 w-4" /></ToolButton>
        <span className="mx-1 w-px bg-slate-200" />
        <ToolButton label="Undo" disabled={!state.canUndo} onClick={() => chain().undo().run()}><Undo2 className="h-4 w-4" /></ToolButton>
        <ToolButton label="Redo" disabled={!state.canRedo} onClick={() => chain().redo().run()}><Redo2 className="h-4 w-4" /></ToolButton>
      </div>
      <EditorContent editor={editor} />
      <MediaPickerModal open={pickerOpen} onClose={() => setPickerOpen(false)} folder="pages" onPick={(m) => chain().setImage({ src: m.url, alt: m.alt || '' }).run()} />
    </div>
  )
}
