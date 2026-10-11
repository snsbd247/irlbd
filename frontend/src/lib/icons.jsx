import {
  Award, BadgeCheck, ChartColumn, Milk, Box, Boxes, Building2, Calendar, CircleCheck, ClipboardCheck, Clock,
  Container, Cpu, Cylinder, Droplet, Eye, Factory, FlaskConical, Gem, Globe, Hammer, Handshake, Headphones,
  Heart, Layers, Leaf, Lightbulb, Mail, MapPin, Microscope, Package, PenTool, Phone, Recycle, Rocket, Ruler,
  ScanSearch, Settings, ShieldCheck, Smile, Sparkles, Star, Target, Thermometer, TrendingUp, Trophy, Truck,
  Users, Wrench, Zap,
} from 'lucide-react'

/** Allow-listed icon names (must match backend config/cms.php "icons"). */
export const ICONS = {
  award: Award,
  'badge-check': BadgeCheck,
  'bar-chart': ChartColumn,
  bottle: Milk,
  box: Box,
  boxes: Boxes,
  building: Building2,
  calendar: Calendar,
  'check-circle': CircleCheck,
  'clipboard-check': ClipboardCheck,
  clock: Clock,
  container: Container,
  cpu: Cpu,
  cylinder: Cylinder,
  droplet: Droplet,
  eye: Eye,
  factory: Factory,
  flask: FlaskConical,
  gem: Gem,
  globe: Globe,
  hammer: Hammer,
  handshake: Handshake,
  headphones: Headphones,
  heart: Heart,
  layers: Layers,
  leaf: Leaf,
  lightbulb: Lightbulb,
  mail: Mail,
  'map-pin': MapPin,
  microscope: Microscope,
  package: Package,
  'pen-tool': PenTool,
  phone: Phone,
  recycle: Recycle,
  rocket: Rocket,
  ruler: Ruler,
  scan: ScanSearch,
  settings: Settings,
  'shield-check': ShieldCheck,
  smile: Smile,
  sparkles: Sparkles,
  star: Star,
  target: Target,
  thermometer: Thermometer,
  'trending-up': TrendingUp,
  trophy: Trophy,
  truck: Truck,
  users: Users,
  wrench: Wrench,
  zap: Zap,
}

export function Icon({ name, fallback = 'sparkles', ...props }) {
  const Component = ICONS[name] || ICONS[fallback]
  return <Component aria-hidden="true" strokeWidth={1.75} {...props} />
}

/* Brand marks (not included in lucide). Simple single-path glyphs. */
const brandPaths = {
  whatsapp: 'M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 0 0 4.74 1.21h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2Zm.01 18.15h-.01a8.23 8.23 0 0 1-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.2 8.2 0 0 1-1.26-4.38c0-4.54 3.7-8.23 8.25-8.23 2.2 0 4.27.86 5.83 2.42a8.18 8.18 0 0 1 2.41 5.82c0 4.54-3.7 8.23-8.23 8.23Zm4.52-6.16c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.12-.16.25-.64.81-.78.97-.14.17-.29.19-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.02-.38.11-.51.11-.11.25-.29.37-.43.13-.15.17-.25.25-.42.08-.16.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.43.06-.66.31-.23.25-.86.85-.86 2.07 0 1.22.89 2.4 1.01 2.56.12.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.14-1.18-.06-.1-.22-.16-.47-.28Z',
  facebook: 'M14 8.5V6.6c0-.8.2-1.3 1.4-1.3H17V2.2C16.7 2.1 15.7 2 14.6 2 12.2 2 10.6 3.4 10.6 6v2.5H8V12h2.6v10H14V12h2.7l.4-3.5H14Z',
  x: 'M17.8 3H21l-7 8 8.2 10h-6.4l-5-6.1L5 21H1.8l7.5-8.6L1.5 3H8l4.5 5.6L17.8 3Zm-1.1 16.1h1.8L7.4 4.8H5.5l11.2 14.3Z',
  linkedin: 'M4.98 3.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5ZM3 9.5h4V21H3V9.5Zm6.5 0h3.8v1.6h.1c.5-1 1.8-2 3.8-2 4 0 4.8 2.6 4.8 6V21h-4v-5.2c0-1.2 0-2.8-1.7-2.8s-2 1.3-2 2.7V21h-4V9.5Z',
  youtube: 'M23 7.2a3 3 0 0 0-2.1-2.1C19 4.6 12 4.6 12 4.6s-7 0-8.9.5A3 3 0 0 0 1 7.2 31 31 0 0 0 .5 12 31 31 0 0 0 1 16.8a3 3 0 0 0 2.1 2.1c1.9.5 8.9.5 8.9.5s7 0 8.9-.5a3 3 0 0 0 2.1-2.1c.4-1.6.5-4.8.5-4.8s0-3.2-.5-4.8ZM9.8 15.1V8.9l5.8 3.1-5.8 3.1Z',
  instagram: 'M12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10Zm0 8.2a3.2 3.2 0 1 1 0-6.4 3.2 3.2 0 0 1 0 6.4ZM17.3 5.5a1.2 1.2 0 1 0 0 2.4 1.2 1.2 0 0 0 0-2.4ZM21.9 7.9c-.1-1.6-.4-3-1.6-4.2S17.7 2.2 16.1 2.1C14.4 2 9.6 2 7.9 2.1c-1.6.1-3 .4-4.2 1.6S2.2 6.3 2.1 7.9C2 9.6 2 14.4 2.1 16.1c.1 1.6.4 3 1.6 4.2s2.6 1.5 4.2 1.6c1.7.1 6.5.1 8.2 0 1.6-.1 3-.4 4.2-1.6s1.5-2.6 1.6-4.2c.1-1.7.1-6.5 0-8.2Zm-2.1 10.1a3.3 3.3 0 0 1-1.9 1.9c-1.3.5-4.4.4-5.9.4s-4.6.1-5.9-.4a3.3 3.3 0 0 1-1.9-1.9c-.5-1.3-.4-4.4-.4-5.9s-.1-4.6.4-5.9a3.3 3.3 0 0 1 1.9-1.9c1.3-.5 4.4-.4 5.9-.4s4.6-.1 5.9.4a3.3 3.3 0 0 1 1.9 1.9c.5 1.3.4 4.4.4 5.9s.1 4.6-.4 5.9Z',
}

export function BrandIcon({ name, className = 'h-4 w-4' }) {
  return (
    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" className={className}>
      <path d={brandPaths[name]} />
    </svg>
  )
}

export const SOCIALS = [
  ['social_facebook', 'facebook', 'Facebook'],
  ['social_x', 'x', 'X (Twitter)'],
  ['social_linkedin', 'linkedin', 'LinkedIn'],
  ['social_youtube', 'youtube', 'YouTube'],
  ['social_instagram', 'instagram', 'Instagram'],
]
