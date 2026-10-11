import { useEffect, useState } from 'react'
import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import {
  Award, Boxes, MailPlus, ScrollText, Factory, BriefcaseBusiness, Camera, UserSearch, MessageSquareQuote, Briefcase, Building2, CircleHelp, ExternalLink, FileText, History, House, Images, Inbox, LayoutDashboard,
  FileSignature, LogOut, Mail, Menu, Navigation, Newspaper, Package, PanelsTopLeft, Settings, Shield, Store, Tags, User, UserCog, Users, X,
} from 'lucide-react'
import { Helmet } from 'react-helmet-async'
import { api } from '../lib/api'
import { cn } from '../lib/utils'
import { useAuth } from './auth'

const NAV = [
  { group: null, items: [{ to: '/admin', label: 'Dashboard', icon: LayoutDashboard, end: true }] },
  {
    group: 'Website',
    items: [
      { to: '/admin/homepage', label: 'Homepage builder', icon: House, perm: 'pages' },
      { to: '/admin/r/pages', label: 'Pages & sections', icon: FileText, perm: 'pages' },
      { to: '/admin/r/hero-slides', label: 'Hero slides', icon: PanelsTopLeft, perm: 'pages' },
      { to: '/admin/menus', label: 'Menus', icon: Navigation, perm: 'menus' },
    ],
  },
  {
    group: 'Catalog',
    items: [
      { to: '/admin/r/products', label: 'Products', icon: Package, perm: 'products' },
      { to: '/admin/r/product-categories', label: 'Product categories', icon: Boxes, perm: 'products' },
      { to: '/admin/r/industries', label: 'Industries', icon: Factory, perm: 'products' },
      { to: '/admin/r/services', label: 'Services', icon: Briefcase, perm: 'services' },
    ],
  },
  {
    group: 'Content',
    items: [
      { to: '/admin/r/news-posts', label: 'News & notices', icon: Newspaper, perm: 'news' },
      { to: '/admin/r/news-categories', label: 'News categories', icon: Tags, perm: 'news' },
      { to: '/admin/r/team-members', label: 'Team & founder', icon: Users, perm: 'team' },
      { to: '/admin/r/milestones', label: 'History milestones', icon: History, perm: 'history' },
      { to: '/admin/r/certifications', label: 'Certifications', icon: Award, perm: 'quality' },
      { to: '/admin/r/gallery-albums', label: 'Gallery', icon: Camera, perm: 'gallery' },
      { to: '/admin/r/clients', label: 'Clients', icon: Building2, perm: 'clients' },
      { to: '/admin/r/testimonials', label: 'Testimonials', icon: MessageSquareQuote, perm: 'clients' },
      { to: '/admin/r/offices', label: 'Offices', icon: Building2, perm: 'contact' },
      { to: '/admin/r/dealers', label: 'Dealers', icon: Store, perm: 'contact' },
      { to: '/admin/r/faqs', label: 'FAQs', icon: CircleHelp, perm: 'contact' },
      { to: '/admin/media', label: 'Media library', icon: Images, perm: 'media' },
    ],
  },
  {
    group: 'Careers',
    items: [
      { to: '/admin/r/job-posts', label: 'Job posts', icon: BriefcaseBusiness, perm: 'careers' },
      { to: '/admin/job-applications', label: 'Applications', icon: UserSearch, perm: 'careers', badge: 'applications' },
    ],
  },
  {
    group: 'Inbox',
    items: [
      { to: '/admin/quote-requests', label: 'Quote requests', icon: FileSignature, perm: 'enquiries', badge: 'quotes' },
      { to: '/admin/enquiries', label: 'Enquiries', icon: Inbox, perm: 'enquiries', badge: 'enquiries' },
      { to: '/admin/r/newsletter-campaigns', label: 'Send newsletter', icon: MailPlus, perm: 'subscribers' },
      { to: '/admin/subscribers', label: 'Subscribers', icon: Mail, perm: 'subscribers' },
    ],
  },
  {
    group: 'System',
    items: [
      { to: '/admin/settings', label: 'General settings & SEO', icon: Settings, perm: 'settings' },
      { to: '/admin/r/users', label: 'Admin users', icon: UserCog, perm: 'users' },
      { to: '/admin/r/roles', label: 'Roles & permissions', icon: Shield, perm: 'users' },
      { to: '/admin/activity-log', label: 'Activity log', icon: ScrollText, perm: 'users' },
    ],
  },
]

function Sidebar({ onNavigate }) {
  const { can } = useAuth()
  const { data: dashboard } = useQuery({
    queryKey: ['admin', 'dashboard'],
    queryFn: () => api.get('/admin/dashboard').then((r) => r.data),
    staleTime: 60 * 1000,
  })
  const badges = { enquiries: dashboard?.stats?.enquiries?.new, quotes: dashboard?.stats?.quotes?.new, applications: dashboard?.stats?.applications?.new }

  return (
    <nav aria-label="Admin" className="flex-1 overflow-y-auto px-3 py-4">
      {NAV.map(({ group, items }) => {
        const visible = items.filter((i) => !i.perm || can(i.perm))
        if (!visible.length) return null
        return (
          <div key={group || 'main'} className="mb-4">
            {group && <p className="mb-1 px-3 text-[11px] font-semibold tracking-wider text-white/40 uppercase">{group}</p>}
            <ul className="space-y-0.5">
              {visible.map((item) => (
                <li key={item.to}>
                  <NavLink
                    to={item.to}
                    end={item.end}
                    onClick={onNavigate}
                    className={({ isActive }) => cn('flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors', isActive ? 'bg-brand-600 text-white' : 'text-white/75 hover:bg-white/10 hover:text-white')}
                  >
                    <item.icon className="h-4 w-4 shrink-0" aria-hidden="true" />
                    <span className="flex-1">{item.label}</span>
                    {item.badge && badges[item.badge] > 0 && (
                      <span className="rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold text-white" aria-label={`${badges[item.badge]} new`}>{badges[item.badge]}</span>
                    )}
                  </NavLink>
                </li>
              ))}
            </ul>
          </div>
        )
      })}
    </nav>
  )
}

export default function AdminLayout() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const { pathname } = useLocation()
  const [open, setOpen] = useState(false)

  useEffect(() => setOpen(false), [pathname])
  // The admin panel is always English (even if the visitor chose Bengali on the website).
  useEffect(() => {
    document.documentElement.lang = 'en'
  }, [pathname])

  async function signOut() {
    await logout()
    navigate('/admin/login', { replace: true })
  }

  const brand = (
    <div className="flex h-16 items-center justify-between border-b border-white/10 px-5">
      <span className="font-bold text-white">IR Limited <span className="font-normal text-white/60">CMS</span></span>
      <button type="button" className="rounded p-1 text-white/70 lg:hidden" onClick={() => setOpen(false)} aria-label="Close menu"><X className="h-5 w-5" /></button>
    </div>
  )

  return (
    <div className="min-h-screen bg-slate-100">
      <Helmet>
        <title>Admin | IR Limited</title>
        <meta name="robots" content="noindex, nofollow" />
      </Helmet>
      <aside className="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col bg-navy-950 lg:flex">
        {brand}
        <Sidebar />
      </aside>
      {open && (
        <div className="fixed inset-0 z-40 lg:hidden" role="dialog" aria-modal="true" aria-label="Admin menu">
          <button type="button" className="absolute inset-0 bg-black/50" onClick={() => setOpen(false)} aria-label="Close menu" />
          <aside className="relative flex h-full w-72 flex-col bg-navy-950">
            {brand}
            <Sidebar onNavigate={() => setOpen(false)} />
          </aside>
        </div>
      )}
      <div className="lg:pl-64">
        <header className="sticky top-0 z-20 flex h-16 items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 sm:px-6">
          <button type="button" className="rounded-md p-2 text-navy-900 hover:bg-slate-100 lg:hidden" onClick={() => setOpen(true)} aria-label="Open menu">
            <Menu className="h-5 w-5" />
          </button>
          <a href="/" target="_blank" rel="noopener noreferrer" className="hidden items-center gap-1.5 text-sm text-slate-600 hover:text-brand-600 sm:flex">
            <ExternalLink className="h-4 w-4" aria-hidden="true" /> View website
          </a>
          <div className="flex items-center gap-2">
            <NavLink to="/admin/profile" className="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-slate-100">
              <span className="icon-circle h-8 w-8"><User className="h-4 w-4" aria-hidden="true" /></span>
              <span className="hidden text-left sm:block">
                <span className="block font-medium text-navy-900">{user.name}</span>
                <span className="block text-xs text-muted">{user.role}</span>
              </span>
            </NavLink>
            <button type="button" onClick={signOut} className="rounded-md p-2 text-slate-600 hover:bg-red-50 hover:text-red-600" aria-label="Sign out" title="Sign out">
              <LogOut className="h-4 w-4" />
            </button>
          </div>
        </header>
        <main className="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
