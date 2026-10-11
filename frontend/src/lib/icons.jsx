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
