import { useEffect, useState } from 'react'

/**
 * Per-visitor product comparison list (max 4 slugs), kept in localStorage purely as a
 * browsing convenience. Every read/write is guarded: private mode or blocked storage
 * simply means the list is not remembered between visits.
 */
const KEY = 'ir-compare'
const EVENT = 'ir-compare-change'
export const COMPARE_MAX = 4

function read() {
  try {
    const value = JSON.parse(localStorage.getItem(KEY) || '[]')
    return Array.isArray(value) ? value.filter((s) => typeof s === 'string').slice(0, COMPARE_MAX) : []
  } catch {
    return []
  }
}

let memory = null // fallback when localStorage is unavailable

function write(list) {
  memory = list
  try {
    localStorage.setItem(KEY, JSON.stringify(list))
  } catch {
    // storage blocked: keep the in-memory copy for this page view
  }
  window.dispatchEvent(new Event(EVENT))
}

export function useCompare() {
  const [list, setList] = useState(() => memory ?? read())

  useEffect(() => {
    const sync = () => setList(memory ?? read())
    window.addEventListener(EVENT, sync)
    window.addEventListener('storage', sync)
    return () => {
      window.removeEventListener(EVENT, sync)
      window.removeEventListener('storage', sync)
    }
  }, [])

  return {
    list,
    has: (slug) => list.includes(slug),
    full: list.length >= COMPARE_MAX,
    toggle: (slug) => write(list.includes(slug) ? list.filter((s) => s !== slug) : [...list, slug].slice(0, COMPARE_MAX)),
    remove: (slug) => write(list.filter((s) => s !== slug)),
    clear: () => write([]),
  }
}
