import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'
import { defineConfig, loadEnv } from 'vite'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  // In development the Laravel API runs on :8000 and is proxied, so the SPA and API
  // share one origin (cookies + CSRF behave exactly like production on one domain).
  const backend = env.DEV_API_PROXY_TARGET || 'http://127.0.0.1:8000'

  return {
    plugins: [react(), tailwindcss()],
    server: {
      port: 5175,
      strictPort: true,
      proxy: {
        '/api': { target: backend, changeOrigin: false },
        '/sanctum': { target: backend, changeOrigin: false },
        '/uploads': { target: backend, changeOrigin: true },
      },
    },
    build: {
      sourcemap: false,
      chunkSizeWarningLimit: 700,
      rollupOptions: {
        output: {
          manualChunks(id) {
            if (id.includes('node_modules')) {
              // TipTap is reached only through the lazily loaded admin editor, so it is
              // split automatically; forcing a manual chunk pulled shared helpers into it
              // and made every public page preload the editor.
              if (id.includes('@tiptap') || id.includes('prosemirror')) return undefined
              if (id.includes('react-router') || id.includes('react-dom') || id.includes('/react/') || id.includes('scheduler')) return 'react'
              if (id.includes('@tanstack') || id.includes('axios')) return 'data'
            }
          },
        },
      },
    },
  }
})
