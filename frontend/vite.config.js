import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    port: 5173,
    open: false, // Ne pas ouvrir automatiquement le navigateur
    strictPort: true,
    watch: {
      usePolling: false, // Plus rapide sur Windows
    },
  },
  optimizeDeps: {
    include: ['vue', 'vue-router', 'axios'],
  },
  build: {
    minify: 'esbuild',
    target: 'esnext',
  },
  define: {
    'process.env.DEBUG': false,
  },
})
