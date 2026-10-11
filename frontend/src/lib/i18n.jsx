import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import { setApiLanguage } from './api'

/**
 * Website language (English default, Bengali optional).
 * - Content comes translated from the API (?lang=bn), falling back to English.
 * - Fixed interface text uses the dictionary below; missing entries stay English.
 * The admin panel is always English.
 */
export const LANGUAGES = { en: 'English', bn: 'বাংলা' }
const KEY = 'ir-lang'

const BN = {
  // Header / navigation
  'Search': 'খুঁজুন',
  'Search the website': 'ওয়েবসাইটে খুঁজুন',
  'Search products, news and pages…': 'পণ্য, সংবাদ ও পেজ খুঁজুন…',
  'Open menu': 'মেনু খুলুন',
  'Close menu': 'মেনু বন্ধ করুন',
  'Home': 'হোম',
  'Skip to content': 'মূল বিষয়বস্তুতে যান',
  // Footer
  'Quick Links': 'দ্রুত লিংক',
  'Our Products': 'আমাদের পণ্য',
  'Newsletter': 'নিউজলেটার',
  'Subscribe': 'সাবস্ক্রাইব',
  'Enter your email address': 'আপনার ইমেইল ঠিকানা লিখুন',
  'Email address': 'ইমেইল ঠিকানা',
  'Back to top': 'উপরে যান',
  'Download Brochure': 'ব্রোশিওর ডাউনলোড',
  // Common actions
  'Read More': 'আরও পড়ুন',
  'Learn More': 'আরও জানুন',
  'View Details': 'বিস্তারিত দেখুন',
  'View Products': 'পণ্য দেখুন',
  'View All Products': 'সব পণ্য দেখুন',
  'View All News': 'সব সংবাদ দেখুন',
  'Request a Quote': 'কোটেশন চান',
  'Get a Quote': 'কোটেশন নিন',
  'Contact Us': 'যোগাযোগ করুন',
  'Try again': 'আবার চেষ্টা করুন',
  'Compare': 'তুলনা',
  'Added to compare': 'তুলনায় যোগ হয়েছে',
  'Clear': 'মুছুন',
  'Clear filters': 'ফিল্টার মুছুন',
  'Back to Products': 'পণ্যে ফিরে যান',
  'Download Datasheet': 'ডেটাশিট ডাউনলোড',
  'Play video': 'ভিডিও চালান',
  // States
  'Something went wrong': 'কিছু একটা সমস্যা হয়েছে',
  'We could not load this content. Please try again.': 'বিষয়বস্তু লোড করা যায়নি। আবার চেষ্টা করুন।',
  'Page not found': 'পেজটি পাওয়া যায়নি',
  'The page you are looking for doesn’t exist or has been moved.': 'আপনি যে পেজটি খুঁজছেন তা নেই অথবা সরানো হয়েছে।',
  'Go to homepage': 'হোমপেজে যান',
  'View products': 'পণ্য দেখুন',
  // Products
  'All Products': 'সব পণ্য',
  'All industries': 'সব শিল্পখাত',
  'Search products…': 'পণ্য খুঁজুন…',
  'No products found': 'কোনো পণ্য পাওয়া যায়নি',
  'Try another category or search term.': 'অন্য ক্যাটাগরি বা শব্দ দিয়ে চেষ্টা করুন।',
  'Showing {from}–{to} of {total} products': '{total}টি পণ্যের মধ্যে {from}–{to} দেখানো হচ্ছে',
  'Product code': 'পণ্য কোড',
  'Specifications': 'স্পেসিফিকেশন',
  'Product Details': 'পণ্যের বিস্তারিত',
  'Related Products': 'সম্পর্কিত পণ্য',
  'Suitable for': 'উপযোগী',
  'Available Sizes & Variants': 'উপলব্ধ সাইজ ও ধরন',
  'Variant': 'ধরন',
  'Capacity': 'ধারণক্ষমতা',
  'Weight': 'ওজন',
  'Neck size': 'নেক সাইজ',
  'Colour': 'রং',
  'MOQ': 'ন্যূনতম অর্ডার',
  'Featured Products': 'বিশেষ পণ্য',
  // Compare
  'Compare Products': 'পণ্য তুলনা',
  'Side-by-side specifications of the products you selected.': 'আপনার বাছাই করা পণ্যগুলোর পাশাপাশি তুলনা।',
  'No products selected': 'কোনো পণ্য বাছাই করা হয়নি',
  'Browse products': 'পণ্য দেখুন',
  '{n} product(s) selected': '{n}টি পণ্য বাছাই করা হয়েছে',
  'Category': 'ক্যাটাগরি',
  'Industries': 'শিল্পখাত',
  'Available sizes': 'উপলব্ধ সাইজ',
  // News
  'All News': 'সব সংবাদ',
  'Categories': 'ক্যাটাগরি',
  'Recent News': 'সাম্প্রতিক সংবাদ',
  'Search news…': 'সংবাদ খুঁজুন…',
  'No news found': 'কোনো সংবাদ পাওয়া যায়নি',
  'Subscribe to get the latest news and updates.': 'সর্বশেষ সংবাদ পেতে সাবস্ক্রাইব করুন।',
  'Share:': 'শেয়ার করুন:',
  '← Back to all news': '← সব সংবাদে ফিরে যান',
  'News': 'সংবাদ',
  // Contact
  'Call Us': 'কল করুন',
  'Email Us': 'ইমেইল করুন',
  'Our Location': 'আমাদের ঠিকানা',
  'Support': 'সহায়তা',
  'We reply within 24 hours': 'আমরা ২৪ ঘণ্টার মধ্যে উত্তর দিই',
  'For product inquiries, quotations or other support, our team is ready to help you.': 'পণ্য, কোটেশন বা অন্য যেকোনো সহায়তার জন্য আমাদের টিম প্রস্তুত।',
  'Send us a message': 'আমাদের বার্তা পাঠান',
  'Get in Touch': 'যোগাযোগ করুন',
  'Fill out the form below and our team will get back to you shortly.': 'নিচের ফর্মটি পূরণ করুন, আমাদের টিম শীঘ্রই আপনার সাথে যোগাযোগ করবে।',
  'Your Name': 'আপনার নাম',
  'Your Email': 'আপনার ইমেইল',
  'Phone Number': 'ফোন নম্বর',
  'Subject': 'বিষয়',
  'Select a subject': 'একটি বিষয় বাছাই করুন',
  'Message': 'বার্তা',
  'Send Message': 'বার্তা পাঠান',
  'Message sent': 'বার্তা পাঠানো হয়েছে',
  'Send another message': 'আরেকটি বার্তা পাঠান',
  'Find us here': 'আমাদের খুঁজে নিন',
  'Visit our office or factory.': 'আমাদের অফিস বা কারখানায় আসুন।',
  'Open in Google Maps': 'গুগল ম্যাপে খুলুন',
  'Our Offices': 'আমাদের অফিস',
  'We look forward to hearing from you and exploring how we can work together.': 'আপনার সাথে একসাথে কাজ করার অপেক্ষায় আছি।',
  'Please enter your name.': 'অনুগ্রহ করে আপনার নাম লিখুন।',
  'Please enter a valid email address.': 'অনুগ্রহ করে সঠিক ইমেইল ঠিকানা লিখুন।',
  'Please enter a valid phone number.': 'অনুগ্রহ করে সঠিক ফোন নম্বর লিখুন।',
  'Please select a subject.': 'অনুগ্রহ করে একটি বিষয় বাছাই করুন।',
  'Your message must be at least 10 characters.': 'বার্তা কমপক্ষে ১০ অক্ষরের হতে হবে।',
  // Quote
  'Tell us what you need and our sales team will send you a quotation.': 'আপনার প্রয়োজন জানান, আমাদের সেলস টিম আপনাকে কোটেশন পাঠাবে।',
  'What do you need?': 'আপনার কী প্রয়োজন?',
  'Product': 'পণ্য',
  'Custom / not listed': 'কাস্টম / তালিকায় নেই',
  'Quantity': 'পরিমাণ',
  'Needed by': 'কবের মধ্যে প্রয়োজন',
  'Size, colour & specification': 'সাইজ, রং ও স্পেসিফিকেশন',
  'Your details': 'আপনার তথ্য',
  'Company': 'প্রতিষ্ঠান',
  'Phone / WhatsApp': 'ফোন / হোয়াটসঅ্যাপ',
  'Additional details': 'অতিরিক্ত তথ্য',
  'Request Quotation': 'কোটেশন চান',
  'Request received': 'অনুরোধ গ্রহণ করা হয়েছে',
  'Request another quote': 'আরেকটি কোটেশন চান',
  'Please tell us the quantity you need.': 'অনুগ্রহ করে প্রয়োজনীয় পরিমাণ লিখুন।',
  'Fast response': 'দ্রুত সাড়া',
  'Our sales team replies within one working day.': 'আমাদের সেলস টিম এক কর্মদিবসের মধ্যে উত্তর দেয়।',
  'Custom solutions': 'কাস্টম সমাধান',
  'Any size, colour, neck finish or printing on request.': 'চাহিদা অনুযায়ী যেকোনো সাইজ, রং, নেক ফিনিশ বা প্রিন্টিং।',
  'Nationwide delivery': 'দেশব্যাপী ডেলিভারি',
  'On-time delivery across Bangladesh and export on request.': 'সারা বাংলাদেশে সময়মতো ডেলিভারি, চাহিদায় রপ্তানি।',
  // Quote tracking
  'Your reference number': 'আপনার রেফারেন্স নম্বর',
  'We have also emailed it to you. Use it to track your request.': 'এটি আপনার ইমেইলেও পাঠানো হয়েছে। এটি দিয়ে অনুরোধের অবস্থা দেখতে পারবেন।',
  'Track my request': 'অনুরোধের অবস্থা দেখুন',
  'Track your quotation': 'কোটেশনের অবস্থা দেখুন',
  'Enter the reference number from your confirmation email.': 'নিশ্চিতকরণ ইমেইলের রেফারেন্স নম্বরটি লিখুন।',
  'Track': 'ট্র্যাক',
  'Reference number': 'রেফারেন্স নম্বর',
  'Check status': 'অবস্থা দেখুন',
  'Do not have a reference number?': 'রেফারেন্স নম্বর নেই?',
  'Submitted': 'জমা দেওয়া হয়েছে',
  'This request is closed.': 'এই অনুরোধটি বন্ধ করা হয়েছে।',
  'Received': 'গ্রহণ করা হয়েছে',
  'Quotation sent': 'কোটেশন পাঠানো হয়েছে',
  'Order confirmed': 'অর্ডার নিশ্চিত',
  'Message from our team': 'আমাদের টিমের বার্তা',
  'Last updated': 'সর্বশেষ আপডেট',
  'We could not find a quotation request with this reference and email address.': 'এই রেফারেন্স নম্বর ও ইমেইল দিয়ে কোনো কোটেশন অনুরোধ পাওয়া যায়নি।',
  // Careers
  'No open positions right now': 'এই মুহূর্তে কোনো পদ খালি নেই',
  'View & Apply': 'দেখুন ও আবেদন করুন',
  'Apply for this position': 'এই পদে আবেদন করুন',
  'Applications for this position are closed.': 'এই পদের আবেদন বন্ধ হয়ে গেছে।',
  'Full name': 'পূর্ণ নাম',
  'Email': 'ইমেইল',
  'Phone': 'ফোন',
  'CV / Resume': 'সিভি / জীবনবৃত্তান্ত',
  'Choose file': 'ফাইল বাছাই করুন',
  'Cover letter (optional)': 'কভার লেটার (ঐচ্ছিক)',
  'Submit Application': 'আবেদন জমা দিন',
  'Application sent': 'আবেদন পাঠানো হয়েছে',
  'All openings': 'সব পদ',
  'Careers': 'ক্যারিয়ার',
  'Gallery': 'গ্যালারি',
  'This album is empty': 'এই অ্যালবামে কোনো ছবি নেই',
  // Gallery / search / misc
  'All albums': 'সব অ্যালবাম',
  'No albums yet': 'এখনো কোনো অ্যালবাম নেই',
  'Close viewer': 'বন্ধ করুন',
  'Previous image': 'আগের ছবি',
  'Next image': 'পরের ছবি',
  'Enter at least 2 characters to search.': 'খুঁজতে কমপক্ষে ২টি অক্ষর লিখুন।',
  'No results found': 'কোনো ফলাফল পাওয়া যায়নি',
  'Need this service?': 'এই সেবা প্রয়োজন?',
  'Thank you for subscribing to our newsletter!': 'আমাদের নিউজলেটারে সাবস্ক্রাইব করার জন্য ধন্যবাদ!',
  'Chat with us on WhatsApp': 'হোয়াটসঅ্যাপে কথা বলুন',
  'Chat with us on Messenger': 'মেসেঞ্জারে কথা বলুন',
}

const DICTIONARY = { bn: BN }

let current = 'en'
const LanguageContext = createContext({ lang: 'en', setLang: () => {}, t: (s) => s })

function initialLanguage() {
  const fromUrl = new URLSearchParams(window.location.search).get('lang')
  if (fromUrl && LANGUAGES[fromUrl]) return fromUrl
  try {
    const stored = localStorage.getItem(KEY)
    if (stored && LANGUAGES[stored]) return stored
  } catch {
    // storage unavailable
  }
  return 'en'
}

export function translate(text, vars, lang = current) {
  let out = (lang !== 'en' && DICTIONARY[lang]?.[text]) || text
  if (vars) Object.entries(vars).forEach(([k, v]) => (out = out.replaceAll(`{${k}}`, String(v))))
  return out
}

export function LanguageProvider({ children }) {
  const queryClient = useQueryClient()
  const [lang, setLangState] = useState(() => {
    const l = initialLanguage()
    current = l
    setApiLanguage(l)
    return l
  })

  useEffect(() => {
    document.documentElement.lang = lang
  }, [lang])

  const setLang = useCallback(
    (next) => {
      if (!LANGUAGES[next] || next === current) return
      current = next
      setApiLanguage(next)
      try {
        localStorage.setItem(KEY, next)
      } catch {
        // storage unavailable: choice lasts for this visit only
      }
      setLangState(next)
      // Refetch public content in the new language (admin data is language-independent).
      queryClient.invalidateQueries({ predicate: (q) => q.queryKey[0] !== 'admin' })
    },
    [queryClient],
  )

  const value = useMemo(() => ({ lang, setLang, t: (text, vars) => translate(text, vars, lang) }), [lang, setLang])
  return <LanguageContext.Provider value={value}>{children}</LanguageContext.Provider>
}

export const useLang = () => useContext(LanguageContext)
export const useT = () => useContext(LanguageContext).t

/** "EN | বাংলা" switch. */
export function LanguageSwitcher({ className = '', dark = false }) {
  const { lang, setLang } = useLang()
  return (
    <div className={`inline-flex items-center gap-1 text-xs font-semibold ${className}`} role="group" aria-label="Language / ভাষা">
      {Object.entries({ en: 'EN', bn: 'বাংলা' }).map(([code, label], i) => (
        <span key={code} className="inline-flex items-center gap-1">
          {i > 0 && <span className={dark ? 'text-white/40' : 'text-slate-300'} aria-hidden="true">|</span>}
          <button
            type="button"
            lang={code}
            onClick={() => setLang(code)}
            aria-pressed={lang === code}
            className={lang === code ? (dark ? 'text-white underline underline-offset-4' : 'text-brand-600 underline underline-offset-4') : dark ? 'text-white/70 hover:text-white' : 'text-slate-600 hover:text-brand-600'}
          >
            {label}
          </button>
        </span>
      ))}
    </div>
  )
}
