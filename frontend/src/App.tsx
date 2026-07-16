// frontend/src/App.tsx
import React from 'react';
import { Routes, Route, Navigate, Outlet } from 'react-router-dom';
import { ResponsiveLayout } from './components/layout/ResponsiveLayout';
import {
  PublicSiteLayout,
  PublicHomePage,
  PublicSlugPage,
} from './components/layout/PublicSiteLayout';
import { DashboardView } from './components/backend/DashboardView';
import { PagesManager } from './components/backend/PagesManager';
import { MarkdownEditor } from './components/backend/MarkdownEditor';
import { BackupManager } from './components/backend/BackupManager';
import { SettingsView } from './components/backend/SettingsView';
import { UsersManager } from './components/backend/UsersManager';
import { NotificationsOverview } from './components/backend/NotificationsOverview';
import { MediaManager } from './components/backend/MediaManager';
import { NavigationManager } from './components/backend/NavigationManager';
import { CommentsManager } from './components/backend/CommentsManager';
import { MessagesViewer } from './components/backend/MessagesViewer';
import { GitHubSyncPanel } from './components/backend/GitHubSyncPanel';
import { CodeEditor } from './components/CodeEditor/CodeEditor';
import { AuditTrail } from './components/Audit/AuditTrail';
import { LoginModal } from './components/frontend/LoginModal';
import { BlogRenderer } from './components/frontend/BlogRenderer';
import { RegisterModal } from './components/auth/RegisterModal';
import { ForgotPasswordModal } from './components/auth/ForgotPasswordModal';
import { ResetPasswordModal } from './components/auth/ResetPasswordModal';
import { useAuth } from './hooks/useAuth';
import { AdminRoleGuard } from './components/auth/AdminRoleGuard';
import { PreviewPage } from './components/backend/PreviewPage';
import { DeveloperLogsViewer } from './components/backend/DeveloperLogsViewer';
import { debugLog } from './utils/debugLog';

function LoadingScreen() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-slate-950">
      <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600" />
    </div>
  );
}

function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { user, pendingTwoFactor } = useAuth();
  if (!user || pendingTwoFactor) {
    return <Navigate to="/login" replace />;
  }
  return <>{children}</>;
}

function GuestRoute({ children }: { children: React.ReactNode }) {
  const { user, pendingTwoFactor } = useAuth();
  if (user && !pendingTwoFactor) {
    return <Navigate to="/dashboard" replace />;
  }
  return <>{children}</>;
}

function AdminShell() {
  return (
    <ProtectedRoute>
      <AdminRoleGuard>
        <ResponsiveLayout>
          <Outlet />
        </ResponsiveLayout>
      </AdminRoleGuard>
    </ProtectedRoute>
  );
}

function App() {
  const { loading, user, pendingTwoFactor } = useAuth();

  React.useEffect(() => {
    if (!loading) {
      debugLog('app.shell.ready', {
        authenticated: Boolean(user),
        pendingTwoFactor,
        path: window.location.pathname,
      });
    }
  }, [loading, user, pendingTwoFactor]);

  if (loading) {
    return <LoadingScreen />;
  }

  return (
    <Routes>
      <Route
        path="/login"
        element={
          <GuestRoute>
            <LoginModal />
          </GuestRoute>
        }
      />
      <Route
        path="/register"
        element={
          <GuestRoute>
            <RegisterModal />
          </GuestRoute>
        }
      />
      <Route
        path="/forgot-password"
        element={
          <GuestRoute>
            <ForgotPasswordModal />
          </GuestRoute>
        }
      />
      <Route path="/reset-password" element={<ResetPasswordModal />} />

      <Route element={<AdminShell />}>
        <Route path="/dashboard" element={<DashboardView />} />
        <Route path="/pages" element={<PagesManager type="pages" />} />
        <Route path="/pages/:slug" element={<MarkdownEditor type="page" />} />
        <Route path="/articles" element={<PagesManager type="articles" />} />
        <Route path="/articles/:slug" element={<MarkdownEditor type="article" />} />
        <Route path="/media" element={<MediaManager />} />
        <Route path="/navigation" element={<NavigationManager />} />
        <Route path="/comments" element={<CommentsManager />} />
        <Route path="/messages" element={<MessagesViewer />} />
        <Route path="/github" element={<GitHubSyncPanel />} />
        <Route path="/code-editor" element={<CodeEditor />} />
        <Route path="/code-editor/*" element={<CodeEditor />} />
        <Route path="/backups" element={<BackupManager />} />
        <Route path="/audit" element={<AuditTrail />} />
        <Route path="/audit/content/:contentId" element={<AuditTrail />} />
        <Route path="/audit/user/:userId" element={<AuditTrail />} />
        <Route path="/notifications" element={<NotificationsOverview />} />
        <Route path="/settings" element={<SettingsView />} />
        <Route path="/users" element={<UsersManager />} />
        <Route path="/developer/logs" element={<DeveloperLogsViewer />} />
      </Route>

      <Route
        path="/preview/:slug"
        element={
          <ProtectedRoute>
            <AdminRoleGuard>
              <PreviewPage />
            </AdminRoleGuard>
          </ProtectedRoute>
        }
      />

      <Route element={<PublicSiteLayout />}>
        <Route index element={<PublicHomePage />} />
        <Route path="blog" element={<BlogRenderer />} />
        <Route path="blog/:slug" element={<BlogRenderer />} />
        <Route path=":slug" element={<PublicSlugPage />} />
      </Route>

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}

export default App;
