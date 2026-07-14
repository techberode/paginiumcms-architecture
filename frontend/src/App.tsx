// frontend/src/App.tsx
import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { ResponsiveLayout } from './components/layout/ResponsiveLayout';
import { DashboardView } from './components/backend/DashboardView';
import { PagesManager } from './components/backend/PagesManager';
import { MarkdownEditor } from './components/backend/MarkdownEditor';
import { BackupManager } from './components/backend/BackupManager';
import { SettingsView } from './components/backend/SettingsView';
import { UsersManager } from './components/backend/UsersManager';
import { CodeEditor } from './components/CodeEditor/CodeEditor';
import { AuditTrail } from './components/Audit/AuditTrail';
import { LoginModal } from './components/frontend/LoginModal';
import { useAuth } from './hooks/useAuth';

function App() {
  const { user, loading, pendingTwoFactor } = useAuth();

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>
    );
  }

  if (!user || pendingTwoFactor) {
    return <LoginModal />;
  }

  return (
    <ResponsiveLayout>
      <Routes>
        <Route path="/" element={<Navigate to="/dashboard" replace />} />
        <Route path="/dashboard" element={<DashboardView />} />
        <Route path="/pages" element={<PagesManager type="pages" />} />
        <Route path="/pages/:slug" element={<MarkdownEditor type="page" />} />
        <Route path="/articles" element={<PagesManager type="articles" />} />
        <Route path="/articles/:slug" element={<MarkdownEditor type="article" />} />
        <Route path="/code-editor" element={<CodeEditor />} />
        <Route path="/code-editor/*" element={<CodeEditor />} />
        <Route path="/backups" element={<BackupManager />} />
        <Route path="/audit" element={<AuditTrail />} />
        <Route path="/audit/content/:contentId" element={<AuditTrail />} />
        <Route path="/audit/user/:userId" element={<AuditTrail />} />
        <Route path="/settings" element={<SettingsView />} />
        <Route path="/users" element={<UsersManager />} />
        <Route path="*" element={
          <div className="card">
            <div className="card-body text-center py-12">
              <h2 className="text-2xl font-bold text-gray-900 dark:text-white">404</h2>
              <p className="text-gray-500 dark:text-gray-400 mt-2">Page not found</p>
              <a href="/dashboard" className="btn btn-primary mt-4 inline-block">
                Go to Dashboard
              </a>
            </div>
          </div>
        } />
      </Routes>
    </ResponsiveLayout>
  );
}

export default App;
