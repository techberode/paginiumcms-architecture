// frontend/src/main.tsx
import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';
import { ThemeProvider } from './context/ThemeContext';
import { NotificationProvider } from './context/NotificationContext';
import { ContentProvider } from './context/ContentContext';
import { SettingsProvider } from './context/SettingsContext';
import { PublicSiteProvider } from './context/PublicSiteContext';
import App from './App';
import './index.css';
import { logFrontendStartup } from './utils/debugLog';
import { DebugRouteTracker } from './components/debug/DebugRouteTracker';

logFrontendStartup();

ReactDOM.createRoot(document.getElementById('root')!).render(
  <React.StrictMode>
    <BrowserRouter>
      <DebugRouteTracker />
      <ThemeProvider>
        <AuthProvider>
          <SettingsProvider>
            <PublicSiteProvider>
              <NotificationProvider>
                <ContentProvider>
                  <App />
                </ContentProvider>
              </NotificationProvider>
            </PublicSiteProvider>
          </SettingsProvider>
        </AuthProvider>
      </ThemeProvider>
    </BrowserRouter>
  </React.StrictMode>
);
