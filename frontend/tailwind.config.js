import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';
import aspectRatio from '@tailwindcss/aspect-ratio';

/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,ts,jsx,tsx}'],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        theme: {
          primary: 'var(--color-primary)',
          'primary-foreground': 'var(--color-primary-foreground)',
          secondary: 'var(--color-secondary)',
          surface: 'var(--color-surface)',
          'surface-elevated': 'var(--color-surface-elevated)',
          text: 'var(--color-text)',
          'text-muted': 'var(--color-text-muted)',
          accent: 'var(--color-accent)',
          border: 'var(--color-border)',
        },
        admin: {
          canvas: 'var(--admin-canvas)',
          sidebar: 'var(--admin-sidebar)',
          'sidebar-muted': 'var(--admin-sidebar-muted)',
          'sidebar-text': 'var(--admin-sidebar-text)',
          'sidebar-hover': 'var(--admin-sidebar-hover)',
          'sidebar-active': 'var(--admin-sidebar-active)',
          'sidebar-active-text': 'var(--admin-sidebar-active-text)',
          primary: 'var(--admin-primary)',
          'primary-hover': 'var(--admin-primary-hover)',
          card: 'var(--admin-card)',
          border: 'var(--admin-border)',
          topbar: 'var(--admin-topbar)',
          'topbar-text': 'var(--admin-topbar-text)',
          'topbar-muted': 'var(--admin-topbar-muted)',
          text: 'var(--admin-text)',
          muted: 'var(--admin-muted)',
        },
        primary: {
          50: '#eef2ff',
          100: '#e0e7ff',
          200: '#c7d2fe',
          300: '#a5b4fc',
          400: '#818cf8',
          500: '#6366f1',
          600: '#4f46e5',
          700: '#4338ca',
          800: '#3730a3',
          900: '#312e81',
        },
        secondary: {
          50: '#f8fafc',
          100: '#f1f5f9',
          200: '#e2e8f0',
          300: '#cbd5e1',
          400: '#94a3b8',
          500: '#64748b',
          600: '#475569',
          700: '#334155',
          800: '#1e293b',
          900: '#0f172a',
        },
      },
      boxShadow: {
        admin: 'var(--admin-shadow)',
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        display: ['Plus Jakarta Sans', 'Inter', 'system-ui', 'sans-serif'],
        mono: ['JetBrains Mono', 'monospace'],
      },
      animation: {
        'fade-in': 'fadeIn 0.3s ease-in-out',
        'slide-up': 'slideUp 0.3s ease-out',
        'fade-in-simple': 'fadeInSimple 0.3s ease-out',
        'slide-in': 'slideIn 0.3s ease-out',
        'spin-slow': 'spin 3s linear infinite',
        'pulse-slow': 'pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
        'bounce-slow': 'bounce 2s infinite',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0', transform: 'translateY(10px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        slideIn: {
          '0%': { transform: 'translateX(-100%)' },
          '100%': { transform: 'translateX(0)' },
        },
        slideUp: {
          '0%': { opacity: '0', transform: 'translate(-50%, 20px)' },
          '100%': { opacity: '1', transform: 'translate(-50%, 0)' },
        },
        fadeInSimple: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
      },
      screens: {
        xs: '475px',
        sm: '640px',
        md: '768px',
        lg: '1024px',
        xl: '1280px',
        '2xl': '1536px',
        '3xl': '1920px',
      },
    },
  },
  plugins: [forms, typography, aspectRatio],
};
