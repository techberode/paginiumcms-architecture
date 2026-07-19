import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  css: {
    postcss: './postcss.config.js',
  },
  server: {
    port: 3025,
    proxy: {
      '/api': {
        target: 'http://localhost:8080',
        changeOrigin: true,
        secure: false,
      },
      '/storage': {
        target: 'http://localhost:8080',
        changeOrigin: true,
        secure: false,
      },
      '/feed.xml': {
        target: 'http://localhost:8080',
        changeOrigin: true,
        secure: false,
      },
      '/sitemap.xml': {
        target: 'http://localhost:8080',
        changeOrigin: true,
        secure: false,
      },
      '/robots.txt': {
        target: 'http://localhost:8080',
        changeOrigin: true,
        secure: false,
      },
    },
  },
  preview: {
    port: 4173,
    proxy: {
      '/api': {
        target: 'http://localhost:8080',
        changeOrigin: true,
        secure: false,
      },
      '/storage': {
        target: 'http://localhost:8080',
        changeOrigin: true,
        secure: false,
      },
      '/feed.xml': {
        target: 'http://localhost:8080',
        changeOrigin: true,
        secure: false,
      },
      '/sitemap.xml': {
        target: 'http://localhost:8080',
        changeOrigin: true,
        secure: false,
      },
      '/robots.txt': {
        target: 'http://localhost:8080',
        changeOrigin: true,
        secure: false,
      },
    },
  },
});
