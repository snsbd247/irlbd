/**
 * Declarative definitions for the generic admin CRUD screens.
 * Every resource maps to /api/v1/admin/{endpoint} and is guarded by `permission`.
 */

const active = { name: 'is_active', label: 'Visible on website', type: 'toggle', default: true }
const status = { name: 'status', label: 'Status', type: 'select', required: true, options: [['draft', 'Draft'], ['published', 'Published']], default: 'draft' }
const slug = { name: 'slug', label: 'URL slug', type: 'text', help: 'Leave empty to generate from the name. Letters, numbers, dashes.' }
const seo = [
  { name: 'meta_title', label: 'SEO title', type: 'text', max: 255, span: 'full', group: 'SEO' },
  { name: 'meta_description', label: 'SEO description', type: 'textarea', max: 500, span: 'full', group: 'SEO' },
]

export const RESOURCES = {
  'hero-slides': {
    title: 'Hero Slides',
    singular: 'slide',
    permission: 'pages',
    description: 'Slides shown in the homepage hero slider.',
    sortable: true,
    columns: [
      { key: 'image_url', type: 'image' },
      { key: 'title', label: 'Title', render: (r) => [r.title, r.highlight, r.title_suffix].filter(Boolean).join(' ') },
      { key: 'is_active', label: 'Visible', type: 'bool' },
    ],
    fields: [
      { name: 'eyebrow', label: 'Small heading', type: 'text', span: 'full' },
      { name: 'title', label: 'Title (white)', type: 'text', required: true },
      { name: 'highlight', label: 'Highlighted word (blue)', type: 'text' },
      { name: 'title_suffix', label: 'Title second line', type: 'text', span: 'full' },
      { name: 'subtitle', label: 'Subtitle', type: 'textarea', span: 'full' },
      { name: 'image', label: 'Background image', type: 'image', folder: 'slides', span: 'full', help: 'Wide image, at least 1600×800px.' },
      { name: 'primary_label', label: 'Primary button label', type: 'text' },
      { name: 'primary_url', label: 'Primary button link', type: 'text', placeholder: '/products' },
      { name: 'secondary_label', label: 'Secondary button label', type: 'text' },
      { name: 'secondary_url', label: 'Secondary button link', type: 'text', placeholder: '/contact' },
      active,
    ],
  },

  'product-categories': {
    title: 'Product Categories',
    singular: 'category',
    permission: 'products',
    sortable: true,
    columns: [
      { key: 'image_url', type: 'image' },
      { key: 'name', label: 'Name' },
      { key: 'products_count', label: 'Products' },
      { key: 'is_active', label: 'Visible', type: 'bool' },
    ],
    fields: [
      { name: 'name', label: 'Name', type: 'text', required: true },
      slug,
      { name: 'icon', label: 'Icon', type: 'icon' },
      { name: 'image', label: 'Image', type: 'image', folder: 'products' },
      { name: 'short_description', label: 'Short description', type: 'textarea', span: 'full', max: 500 },
      { name: 'description', label: 'Description', type: 'richtext', span: 'full' },
      active,
      ...seo,
    ],
  },

  products: {
    title: 'Products',
    singular: 'product',
    permission: 'products',
    sortable: true,
    filters: [
      { name: 'status', label: 'Status', options: [['published', 'Published'], ['draft', 'Draft']] },
      { name: 'category', label: 'Category', endpoint: '/admin/product-categories' },
    ],
    columns: [
      { key: 'image_url', type: 'image' },
      { key: 'name', label: 'Name', sub: (r) => r.sku },
      { key: 'category', label: 'Category', render: (r) => r.category?.name || '—' },
      { key: 'is_featured', label: 'Featured', type: 'bool' },
      { key: 'status', label: 'Status', type: 'status' },
    ],
    fields: [
      { name: 'name', label: 'Product name', type: 'text', required: true },
      slug,
      { name: 'product_category_id', label: 'Category', type: 'relation', endpoint: '/admin/product-categories' },
      { name: 'sku', label: 'SKU / product code', type: 'text' },
      { name: 'short_description', label: 'Short description', type: 'textarea', span: 'full', max: 500 },
      { name: 'image', label: 'Main image', type: 'image', folder: 'products', span: 'full' },
      { name: 'images', label: 'Gallery images', type: 'gallery', folder: 'products', span: 'full', default: [] },
      { name: 'datasheet', label: 'Datasheet (PDF, optional)', type: 'file', folder: 'documents', span: 'full' },
      {
        name: 'specifications', label: 'Specifications', type: 'repeater', span: 'full', addLabel: 'Add specification', default: [],
        fields: [{ name: 'label', label: 'Label', type: 'text' }, { name: 'value', label: 'Value', type: 'text' }],
      },
      { name: 'description', label: 'Full description', type: 'richtext', span: 'full' },
      status,
      { name: 'is_featured', label: 'Featured product', type: 'toggle', default: false },
      ...seo,
    ],
    // API returns images as [{path,url,alt}] already.
  },

  services: {
    title: 'Services',
    singular: 'service',
    permission: 'services',
    sortable: true,
    columns: [
      { key: 'icon', type: 'icon' },
      { key: 'title', label: 'Title', sub: (r) => r.short_description },
      { key: 'is_active', label: 'Visible', type: 'bool' },
    ],
    fields: [
      { name: 'title', label: 'Title', type: 'text', required: true },
      slug,
      { name: 'icon', label: 'Icon', type: 'icon' },
      { name: 'image', label: 'Image (detail page banner)', type: 'image', folder: 'pages' },
      { name: 'short_description', label: 'Short description', type: 'textarea', span: 'full', max: 500 },
      { name: 'description', label: 'Detail page content', type: 'richtext', span: 'full' },
      active,
    ],
  },

  clients: {
    title: 'Clients',
    singular: 'client',
    permission: 'clients',
    description: 'Only add logos you have permission to display.',
    sortable: true,
    columns: [
      { key: 'logo_url', type: 'image' },
      { key: 'name', label: 'Name' },
      { key: 'is_active', label: 'Visible', type: 'bool' },
    ],
    fields: [
      { name: 'name', label: 'Client name', type: 'text', required: true },
      { name: 'website_url', label: 'Website', type: 'text', placeholder: 'https://' },
      { name: 'logo', label: 'Logo', type: 'image', folder: 'clients', span: 'full' },
      active,
    ],
  },

  'team-members': {
    title: 'Team & Founder',
    singular: 'member',
    permission: 'team',
    description: 'The first active member marked "Founder" appears on the Founder Message page.',
    sortable: true,
    columns: [
      { key: 'photo_url', type: 'image' },
      { key: 'name', label: 'Name', sub: (r) => r.designation },
      { key: 'is_founder', label: 'Founder', type: 'bool' },
      { key: 'is_active', label: 'Visible', type: 'bool' },
    ],
    fields: [
      { name: 'name', label: 'Name', type: 'text', required: true },
      { name: 'designation', label: 'Designation', type: 'text' },
      { name: 'photo', label: 'Portrait', type: 'image', folder: 'team' },
      { name: 'signature', label: 'Signature (optional, transparent PNG)', type: 'image', folder: 'team' },
      { name: 'quote', label: 'Highlight quote (optional)', type: 'textarea', span: 'full', max: 500 },
      { name: 'message', label: 'Message', type: 'richtext', span: 'full', minHeight: 320 },
      { name: 'is_founder', label: 'Show as founder', type: 'toggle', default: false },
      active,
    ],
  },

  'news-categories': {
    title: 'News Categories',
    singular: 'category',
    permission: 'news',
    sortable: true,
    columns: [
      { key: 'name', label: 'Name' },
      { key: 'posts_count', label: 'Posts' },
      { key: 'is_active', label: 'Visible', type: 'bool' },
    ],
    fields: [{ name: 'name', label: 'Name', type: 'text', required: true }, slug, active],
  },

  'news-posts': {
    title: 'News & Notices',
    singular: 'post',
    permission: 'news',
    filters: [
      { name: 'status', label: 'Status', options: [['published', 'Published'], ['draft', 'Draft']] },
      { name: 'category', label: 'Category', endpoint: '/admin/news-categories' },
    ],
    columns: [
      { key: 'featured_image_url', type: 'image' },
      { key: 'title', label: 'Title', sub: (r) => r.category?.name },
      { key: 'published_at', label: 'Publish date', type: 'date' },
      { key: 'status', label: 'Status', type: 'status' },
    ],
    fields: [
      { name: 'title', label: 'Title', type: 'text', required: true, span: 'full' },
      slug,
      { name: 'news_category_id', label: 'Category', type: 'relation', endpoint: '/admin/news-categories' },
      { name: 'excerpt', label: 'Excerpt', type: 'textarea', span: 'full', max: 500 },
      { name: 'featured_image', label: 'Featured image', type: 'image', folder: 'news', span: 'full' },
      { name: 'content', label: 'Content', type: 'richtext', span: 'full', minHeight: 360 },
      status,
      { name: 'published_at', label: 'Publish date', type: 'datetime', help: 'Future dates are scheduled; empty = now when published.' },
      { name: 'is_featured', label: 'Featured', type: 'toggle', default: false },
      ...seo,
    ],
  },

  milestones: {
    title: 'History Milestones',
    singular: 'milestone',
    permission: 'history',
    sortable: true,
    columns: [
      { key: 'image_url', type: 'image' },
      { key: 'year', label: 'Year' },
      { key: 'title', label: 'Title' },
      { key: 'is_active', label: 'Visible', type: 'bool' },
    ],
    fields: [
      { name: 'year', label: 'Year', type: 'text', required: true },
      { name: 'title', label: 'Title', type: 'text', required: true },
      { name: 'description', label: 'Description', type: 'textarea', span: 'full', rows: 4 },
      { name: 'image', label: 'Image', type: 'image', folder: 'history', span: 'full' },
      active,
    ],
  },

  certifications: {
    title: 'Certifications',
    singular: 'certification',
    permission: 'quality',
    description: 'Only certifications marked "verified" are shown publicly. Verify each certificate before enabling it.',
    sortable: true,
    columns: [
      { key: 'logo_url', type: 'image' },
      { key: 'name', label: 'Name' },
      { key: 'is_verified', label: 'Verified', type: 'bool' },
      { key: 'is_active', label: 'Visible', type: 'bool' },
    ],
    fields: [
      { name: 'name', label: 'Name', type: 'text', required: true },
      { name: 'description', label: 'Description / certificate number', type: 'text' },
      { name: 'logo', label: 'Logo', type: 'image', folder: 'certifications', span: 'full' },
      { name: 'is_verified', label: 'I have verified this certification is valid', type: 'toggle', default: false },
      active,
    ],
  },

  offices: {
    title: 'Offices',
    singular: 'office',
    permission: 'contact',
    sortable: true,
    columns: [
      { key: 'image_url', type: 'image' },
      { key: 'name', label: 'Name', sub: (r) => r.phone },
      { key: 'is_active', label: 'Visible', type: 'bool' },
    ],
    fields: [
      { name: 'name', label: 'Office name', type: 'text', required: true },
      { name: 'phone', label: 'Phone', type: 'text' },
      { name: 'email', label: 'Email', type: 'email' },
      { name: 'address', label: 'Address', type: 'textarea', rows: 4 },
      { name: 'image', label: 'Photo', type: 'image', folder: 'pages', span: 'full' },
      active,
    ],
  },

  faqs: {
    title: 'FAQs',
    singular: 'FAQ',
    permission: 'contact',
    sortable: true,
    columns: [
      { key: 'question', label: 'Question' },
      { key: 'is_active', label: 'Visible', type: 'bool' },
    ],
    fields: [
      { name: 'question', label: 'Question', type: 'text', required: true, span: 'full' },
      { name: 'answer', label: 'Answer', type: 'textarea', required: true, span: 'full', rows: 5 },
      active,
    ],
  },

  pages: {
    title: 'Pages',
    singular: 'page',
    permission: 'pages',
    description: 'All website pages. Open a page to edit its content sections.',
    filters: [{ name: 'status', label: 'Status', options: [['published', 'Published'], ['draft', 'Draft']] }],
    columns: [
      { key: 'title', label: 'Title', sub: (r) => r.path },
      { key: 'template', label: 'Type', render: (r) => (r.is_home ? 'Homepage' : { sections: 'Content page', products: 'Product catalog', news: 'News listing', contact: 'Contact page' }[r.template]) },
      { key: 'sections_count', label: 'Sections' },
      { key: 'status', label: 'Status', type: 'status' },
    ],
    editPath: (r) => `/admin/pages/${r.id}`,
    newPath: '/admin/pages/new',
    canDelete: (r) => !r.is_system && !r.is_home,
  },

  users: {
    title: 'Admin Users',
    singular: 'user',
    permission: 'users',
    columns: [
      { key: 'name', label: 'Name', sub: (r) => r.email },
      { key: 'role', label: 'Role', render: (r) => r.role?.name || '—' },
      { key: 'last_login_at', label: 'Last login', type: 'date' },
      { key: 'is_active', label: 'Active', type: 'bool' },
    ],
    fields: [
      { name: 'name', label: 'Name', type: 'text', required: true },
      { name: 'email', label: 'Email', type: 'email', required: true },
      { name: 'role_id', label: 'Role', type: 'relation', endpoint: '/admin/roles', placeholder: '— Select role —' },
      { name: 'password', label: 'Password', type: 'password', help: 'Min. 10 characters with letters and numbers. Leave empty to keep the current password.' },
      { name: 'is_active', label: 'Account active', type: 'toggle', default: true },
    ],
  },

  roles: {
    title: 'Roles & Permissions',
    singular: 'role',
    permission: 'users',
    columns: [
      { key: 'name', label: 'Role', sub: (r) => r.description },
      { key: 'users_count', label: 'Users' },
      { key: 'is_super', label: 'Full access', type: 'bool' },
    ],
    fields: [
      { name: 'name', label: 'Role name', type: 'text', required: true },
      { name: 'description', label: 'Description', type: 'text' },
      { name: 'permissions', label: 'Permissions', type: 'permissions', span: 'full', default: [], showIf: (r) => !r.is_super },
    ],
    canDelete: (r) => !r.is_super && !r.users_count,
  },
}
