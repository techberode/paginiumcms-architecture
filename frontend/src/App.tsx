// frontend/src/App.tsx
import React from 'react';
import { Routes, Route, Navigate, Outlet, useLocation } from 'react-router-dom';
import { ResponsiveLayout } from './components/layout/ResponsiveLayout';
import {
  PublicSiteLayout,
  PublicHomePage,
  PublicSlugPage,
} from './components/layout/PublicSiteLayout';
import { DashboardView } from './components/backend/DashboardView';
import { AnalyticsView } from './components/backend/AnalyticsView';
import { PagesManager } from './components/backend/PagesManager';
import { EditorialCalendarView } from './components/backend/EditorialCalendarView';
import { MarkdownEditor } from './components/backend/MarkdownEditor';
import { BackupManager } from './components/backend/BackupManager';
import { SettingsView } from './components/backend/SettingsView';
import { TranslationEditor } from './components/backend/TranslationEditor';
import { AccountSecurityView } from './components/backend/AccountSecurityView';
import { UsersManager } from './components/backend/UsersManager';
import { NotificationsOverview } from './components/backend/NotificationsOverview';
import { SchedulerView } from './components/backend/SchedulerView';
import { ExtensionsManager } from './components/backend/ExtensionsManager';
import { MediaManager } from './components/backend/MediaManager';
import { NavigationManager } from './components/backend/NavigationManager';
import { CommentsManager } from './components/backend/CommentsManager';
import { MessagesViewer } from './components/backend/MessagesViewer';
import { NewsletterSubscribersPanel } from './components/backend/NewsletterSubscribersPanel';
import { GalleryManager } from './components/backend/GalleryManager';
import { GitHubSyncPanel } from './components/backend/GitHubSyncPanel';
import { CodeEditor } from './components/CodeEditor/CodeEditor';
import { AuditTrail } from './components/Audit/AuditTrail';
import { LoginModal } from './components/frontend/LoginModal';
import { BlogRenderer } from './components/frontend/BlogRenderer';
import { FeaturesPage } from './components/frontend/FeaturesPage';
import { RegisterModal } from './components/auth/RegisterModal';
import { ForgotPasswordModal } from './components/auth/ForgotPasswordModal';
import { ResetPasswordModal } from './components/auth/ResetPasswordModal';
import { NewsletterConfirmPage } from './components/frontend/NewsletterConfirmPage';
import { NewsletterManagePage } from './components/frontend/NewsletterManagePage';
import { CookiePolicyPage } from './components/frontend/CookiePolicyPage';
import { NewsletterUnsubscribePage } from './components/frontend/NewsletterUnsubscribePage';
import { useAuth } from './hooks/useAuth';
import { AdminRoleGuard } from './components/auth/AdminRoleGuard';
import { PreviewPage } from './components/backend/PreviewPage';
import { DeveloperLogsViewer } from './components/backend/DeveloperLogsViewer';
import { TrashManager } from './components/backend/TrashManager';
import { FirewallManager } from './components/backend/FirewallManager';
import { LogsManager } from './components/backend/LogsManager';
import { SecurityAuditManager } from './components/backend/SecurityAuditManager';
import { BlueprintManager } from './components/backend/BlueprintManager';
import { DemoManager } from './components/backend/DemoManager';
import { SystemUpdateView } from './components/backend/SystemUpdateView';
import { ApiKeysManager } from './components/backend/ApiKeysManager';
import { RedirectsManager } from './components/backend/RedirectsManager';
import { ShortcodesManager } from './components/backend/ShortcodesManager';
import { CategoriesManager } from './components/backend/CategoriesManager';
import { RolesManager } from './components/backend/RolesManager';
import { SnippetsManager } from './components/backend/SnippetsManager';
import { OriginPanelView } from './components/backend/OriginPanelView';
import { WebhooksManager } from './components/backend/WebhooksManager';
import { debugLog } from './utils/debugLog';
import { ADMIN_DEFAULT_ROUTE } from './config/adminNavSections';

function LoadingScreen() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-slate-950">
      <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600" />
    </div>
  );
}

function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { user, pendingTwoFactor, twoFactorSetupPending } = useAuth();
  const location = useLocation();

  if (!user) {
    return <Navigate to="/login" replace />;
  }

  if (pendingTwoFactor && twoFactorSetupPending) {
    if (location.pathname !== '/account/security') {
      return <Navigate to="/account/security" replace state={{ setup2fa: true }} />;
    }
    return <>{children}</>;
  }

  if (pendingTwoFactor) {
    return <Navigate to="/login" replace />;
  }

  return <>{children}</>;
}

function GuestRoute({ children }: { children: React.ReactNode }) {
  const { user, pendingTwoFactor, twoFactorSetupPending } = useAuth();

  if (user && !pendingTwoFactor) {
    return <Navigate to={twoFactorSetupPending ? '/account/security' : ADMIN_DEFAULT_ROUTE} replace />;
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
  const location = useLocation();

  React.useEffect(() => {
    if (!loading) {
      debugLog('app.shell.ready', {
        authenticated: Boolean(user),
        pendingTwoFactor,
        path: location.pathname,
      });
    }
  }, [loading, user, pendingTwoFactor, location.pathname]);

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
      <Route path="/newsletter/confirm" element={<NewsletterConfirmPage />} />
      <Route path="/newsletter/manage" element={<NewsletterManagePage />} />
      <Route path="/newsletter/unsubscribe" element={<NewsletterUnsubscribePage />} />

      <Route element={<AdminShell />}>
        <Route path="/dashboard" element={<DashboardView />} />
        <Route path="/analytics" element={<AnalyticsView />} />
        <Route path="/pages" element={<PagesManager type="pages" />} />
        <Route path="/pages/:slug" element={<MarkdownEditor type="page" />} />
        <Route path="/articles" element={<PagesManager type="articles" />} />
        <Route path="/articles/:slug" element={<MarkdownEditor type="article" />} />
        <Route path="/categories" element={<CategoriesManager />} />
        <Route path="/platform/editorial-calendar" element={<EditorialCalendarView />} />
        <Route path="/media" element={<MediaManager />} />
        <Route path="/navigation" element={<NavigationManager />} />
        <Route path="/comments" element={<CommentsManager />} />
        <Route path="/messages" element={<MessagesViewer />} />
        <Route path="/newsletter" element={<NewsletterSubscribersPanel />} />
        <Route path="/gallery" element={<GalleryManager />} />
        <Route path="/github" element={<GitHubSyncPanel />} />
        <Route path="/code-editor" element={<CodeEditor />} />
        <Route path="/code-editor/*" element={<CodeEditor />} />
        <Route path="/backups" element={<BackupManager />} />
        <Route path="/trash" element={<TrashManager />} />
        <Route path="/firewall" element={<FirewallManager />} />
        <Route path="/logs" element={<LogsManager />} />
        <Route path="/audit" element={<AuditTrail />} />
        <Route path="/audit/content/:contentId" element={<AuditTrail />} />
        <Route path="/audit/user/:userId" element={<AuditTrail />} />
        <Route path="/security/audit" element={<SecurityAuditManager />} />
        <Route path="/security/roles" element={<RolesManager />} />
        <Route path="/security/acl" element={<Navigate to="/settings?category=security&group=accessControl" replace />} />
        <Route path="/blueprints" element={<BlueprintManager />} />
        <Route path="/extensions" element={<ExtensionsManager />} />
        <Route path="/demo" element={<DemoManager />} />
        <Route path="/notifications" element={<NotificationsOverview />} />
        <Route path="/scheduler" element={<SchedulerView />} />
        <Route path="/platform/update" element={<SystemUpdateView />} />
        <Route path="/platform/api-keys" element={<ApiKeysManager />} />
        <Route path="/platform/redirects" element={<RedirectsManager />} />
        <Route path="/platform/webhooks" element={<WebhooksManager />} />
        <Route path="/platform/shortcodes" element={<ShortcodesManager />} />
        <Route path="/platform/snippets" element={<SnippetsManager />} />
        <Route path="/platform/origin" element={<OriginPanelView />} />
        <Route path="/settings" element={<SettingsView />} />
        <Route path="/translations" element={<TranslationEditor />} />
        <Route path="/account/security" element={<AccountSecurityView />} />
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
        <Route path="features" element={<FeaturesPage />} />
        <Route path="cookies" element={<CookiePolicyPage />} />
        <Route path="blog" element={<BlogRenderer />} />
        <Route path="blog/:slug" element={<BlogRenderer />} />
        <Route path=":slug" element={<PublicSlugPage />} />
      </Route>

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}

export default App;
