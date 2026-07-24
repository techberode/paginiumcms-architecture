// frontend/src/main.tsx
import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from './lib/queryClient';
import { AuthProvider } from './context/AuthContext';
import { ThemeProvider } from './context/ThemeContext';
import { NotificationProvider } from './context/NotificationContext';
import { ContentProvider } from './context/ContentContext';
import { SettingsProvider } from './context/SettingsContext';
import { I18nProvider } from './context/I18nContext';
import { PublicSiteProvider } from './context/PublicSiteContext';
import App from './App';
import { SiteBrandingHead } from './components/branding/SiteBrandingHead';
import './index.css';
import { logFrontendStartup } from './utils/debugLog';
import { DebugRouteTracker } from './components/debug/DebugRouteTracker';
import './i18n/registerModules';

logFrontendStartup();

async function bootstrap(): Promise<void> {
  if (import.meta.env.VITE_MSW === 'true') {
    const { startMockServiceWorker } = await import('./mocks/browser');
    await startMockServiceWorker();
  }

  ReactDOM.createRoot(document.getElementById('root')!).render(
  <React.StrictMode>
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <DebugRouteTracker />
        <ThemeProvider>
          <AuthProvider>
            <SettingsProvider>
              <SiteBrandingHead />
              <I18nProvider>
                <PublicSiteProvider>
                  <NotificationProvider>
                    <ContentProvider>
                      <App />
                    </ContentProvider>
                  </NotificationProvider>
                </PublicSiteProvider>
              </I18nProvider>
            </SettingsProvider>
          </AuthProvider>
        </ThemeProvider>
      </BrowserRouter>
    </QueryClientProvider>
  </React.StrictMode>
  );
}

void bootstrap();
