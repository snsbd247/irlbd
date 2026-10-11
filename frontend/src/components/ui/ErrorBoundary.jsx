import { Component } from 'react'
import { ErrorState } from './States'

const isChunkError = (error) => /Failed to fetch dynamically imported module|Importing a module script failed|ChunkLoadError|Loading chunk/i.test(error?.message || '')

/**
 * Catches render errors. After a redeploy, old lazy-loaded chunks no longer exist on the
 * server; in that case the page reloads once to pick up the new build.
 */
export default class ErrorBoundary extends Component {
  state = { error: null }

  static getDerivedStateFromError(error) {
    return { error }
  }

  componentDidCatch(error) {
    if (isChunkError(error)) {
      const key = 'chunk-reload-at'
      let last = 0
      try {
        last = Number(sessionStorage.getItem(key)) || 0
        if (Date.now() - last > 10000) {
          sessionStorage.setItem(key, String(Date.now()))
          window.location.reload()
        }
      } catch {
        // storage unavailable: fall through to the error message
      }
    }
  }

  render() {
    if (!this.state.error) return this.props.children
    return (
      <div className="container-x py-20">
        <ErrorState
          title={isChunkError(this.state.error) ? 'A new version of the site is available' : 'Something went wrong'}
          message="Please reload the page."
          onRetry={() => window.location.reload()}
        />
      </div>
    )
  }
}
