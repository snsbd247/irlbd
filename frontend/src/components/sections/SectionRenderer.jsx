import { Fragment } from 'react'
import HeroSlider from './HeroSlider'
import { AboutIntro, CtaBanner, FeatureStrip, IconCards, ImageText, NewsletterSection, ProcessSteps, RichTextSection, Stats } from './ContentSections'
import { Certifications, Clients, Faq, FeaturedProducts, FounderMessage, LatestNews, ProductCategories, ServicesGrid, Timeline } from './DataSections'

/** Approved section types → components. Unknown types are ignored (never rendered as raw HTML). */
const REGISTRY = {
  hero_slider: HeroSlider,
  feature_strip: FeatureStrip,
  about_intro: AboutIntro,
  services_grid: ServicesGrid,
  product_categories: ProductCategories,
  featured_products: FeaturedProducts,
  process_steps: ProcessSteps,
  clients: Clients,
  latest_news: LatestNews,
  image_text: ImageText,
  icon_cards: IconCards,
  stats: Stats,
  founder_message: FounderMessage,
  timeline: Timeline,
  certifications: Certifications,
  faq: Faq,
  rich_text: RichTextSection,
  cta_banner: CtaBanner,
  newsletter: NewsletterSection,
}

/**
 * Renders a page's sections in order. `module` (product catalog, news list, contact form)
 * is placed where a "module_content" section sits, or after all sections if there is none.
 */
export default function SectionRenderer({ sections = [], module = null }) {
  const hasMarker = sections.some((s) => s.type === 'module_content')

  return (
    <>
      {sections.map((section) => {
        if (section.type === 'module_content') {
          return <Fragment key={section.id}>{module && module(section)}</Fragment>
        }
        const Component = REGISTRY[section.type]
        return Component ? <Component key={section.id} section={section} /> : null
      })}
      {!hasMarker && module && module(null)}
    </>
  )
}
