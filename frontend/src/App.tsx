// frontend/src/App.tsx
import React from 'react';
import { Routes, Route, Navigate, Outlet, useLocation, useParams } from 'react-router-dom';
import { settingsGroupPath } from './utils/adminDeepLinks';
import { ResponsiveLayout } from './components/layout/ResponsiveLayout';
import {
  PublicSiteLayout,
  PublicHomePage,
  PublicSlugPage,
  PublicNotFoundCatchAll,
} from './components/layout/PublicSiteLayout';
import { AdminRouteErrorBoundary } from './components/layout/AdminRouteErrorBoundary';
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
import { SetupWizardView } from './components/setup/SetupWizardView';
import { useSetupStatus } from './hooks/useSetupStatus';
import { debugLog } from './utils/debugLog';
import { ADMIN_DEFAULT_ROUTE } from './config/adminNavSections';
import { postAuthPath } from './utils/postAuthPath';

/** Route-level code splitting: admin views load on demand, outside the public-site bundle. */
function lazyView<P extends object>(load: () => Promise<React.ComponentType<P>>) {
  return React.lazy(async () => ({ default: await load() }));
}

const DashboardView = lazyView(() => import('./components/backend/DashboardView').then((m) => m.DashboardView));
const AnalyticsView = lazyView(() => import('./components/backend/AnalyticsView').then((m) => m.AnalyticsView));
const PagesManager = lazyView(() => import('./components/backend/PagesManager').then((m) => m.PagesManager));
const EditorialCalendarView = lazyView(() =>
  import('./components/backend/EditorialCalendarView').then((m) => m.EditorialCalendarView)
);
const MarkdownEditor = lazyView(() => import('./components/backend/MarkdownEditor').then((m) => m.MarkdownEditor));
const BackupManager = lazyView(() => import('./components/backend/BackupManager').then((m) => m.BackupManager));
const ContentMigrationManager = lazyView(() =>
  import('./components/backend/ContentMigrationManager').then((m) => m.ContentMigrationManager)
);
const SettingsView = lazyView(() => import('./components/backend/SettingsView').then((m) => m.SettingsView));
const TranslationEditor = lazyView(() =>
  import('./components/backend/TranslationEditor').then((m) => m.TranslationEditor)
);
const AccountView = lazyView(() => import('./components/backend/AccountView').then((m) => m.AccountView));
const UsersManager = lazyView(() => import('./components/backend/UsersManager').then((m) => m.UsersManager));
const NotificationsOverview = lazyView(() =>
  import('./components/backend/NotificationsOverview').then((m) => m.NotificationsOverview)
);
const SchedulerView = lazyView(() => import('./components/backend/SchedulerView').then((m) => m.SchedulerView));
const ExtensionsManager = lazyView(() =>
  import('./components/backend/ExtensionsManager').then((m) => m.ExtensionsManager)
);
const ThemesManager = lazyView(() => import('./components/backend/ThemesManager').then((m) => m.ThemesManager));
const ThemeStudioShell = lazyView(() =>
  import('./components/backend/ThemeStudioShell').then((m) => m.ThemeStudioShell)
);
const MediaManager = lazyView(() => import('./components/backend/MediaManager').then((m) => m.MediaManager));
const NavigationManager = lazyView(() =>
  import('./components/backend/NavigationManager').then((m) => m.NavigationManager)
);
const CommentsManager = lazyView(() => import('./components/backend/CommentsManager').then((m) => m.CommentsManager));
const MessagesViewer = lazyView(() => import('./components/backend/MessagesViewer').then((m) => m.MessagesViewer));
const NewsletterSubscribersPanel = lazyView(() =>
  import('./components/backend/NewsletterSubscribersPanel').then((m) => m.NewsletterSubscribersPanel)
);
const GalleryManager = lazyView(() => import('./components/backend/GalleryManager').then((m) => m.GalleryManager));
const GitHubSyncPanel = lazyView(() => import('./components/backend/GitHubSyncPanel').then((m) => m.GitHubSyncPanel));
const CodeEditor = lazyView(() => import('./components/CodeEditor/CodeEditor').then((m) => m.CodeEditor));
const AuditTrail = lazyView(() => import('./components/Audit/AuditTrail').then((m) => m.AuditTrail));
const PreviewPage = lazyView(() => import('./components/backend/PreviewPage').then((m) => m.PreviewPage));
const DeveloperLogsViewer = lazyView(() =>
  import('./components/backend/DeveloperLogsViewer').then((m) => m.DeveloperLogsViewer)
);
const TrashManager = lazyView(() => import('./components/backend/TrashManager').then((m) => m.TrashManager));
const FirewallManager = lazyView(() => import('./components/backend/FirewallManager').then((m) => m.FirewallManager));
const LogsManager = lazyView(() => import('./components/backend/LogsManager').then((m) => m.LogsManager));
const SecurityAuditManager = lazyView(() =>
  import('./components/backend/SecurityAuditManager').then((m) => m.SecurityAuditManager)
);
const BlueprintManager = lazyView(() =>
  import('./components/backend/BlueprintManager').then((m) => m.BlueprintManager)
);
const DemoManager = lazyView(() => import('./components/backend/DemoManager').then((m) => m.DemoManager));
const SystemUpdateView = lazyView(() =>
  import('./components/backend/SystemUpdateView').then((m) => m.SystemUpdateView)
);
const ApiKeysManager = lazyView(() => import('./components/backend/ApiKeysManager').then((m) => m.ApiKeysManager));
const RedirectsManager = lazyView(() =>
  import('./components/backend/RedirectsManager').then((m) => m.RedirectsManager)
);
const ShortcodesManager = lazyView(() =>
  import('./components/backend/ShortcodesManager').then((m) => m.ShortcodesManager)
);
const WidgetsManager = lazyView(() => import('./components/backend/WidgetsManager').then((m) => m.WidgetsManager));
const TeamsManager = lazyView(() => import('./components/backend/TeamsManager').then((m) => m.TeamsManager));
const TeamChatView = lazyView(() => import('./components/backend/TeamChatView').then((m) => m.TeamChatView));
const EventsManager = lazyView(() => import('./components/backend/EventsManager').then((m) => m.EventsManager));
const TimeTrackerView = lazyView(() => import('./components/backend/TimeTrackerView').then((m) => m.TimeTrackerView));
const KanbanBoardView = lazyView(() => import('./components/backend/KanbanBoardView').then((m) => m.KanbanBoardView));
const MailInboxView = lazyView(() => import('./components/backend/MailInboxView').then((m) => m.MailInboxView));
const CategoriesManager = lazyView(() =>
  import('./components/backend/CategoriesManager').then((m) => m.CategoriesManager)
);
const RolesManager = lazyView(() => import('./components/backend/RolesManager').then((m) => m.RolesManager));
const SnippetsManager = lazyView(() => import('./components/backend/SnippetsManager').then((m) => m.SnippetsManager));
const OriginPanelView = lazyView(() => import('./components/backend/OriginPanelView').then((m) => m.OriginPanelView));
const PlaygroundView = lazyView(() => import('./components/backend/PlaygroundView').then((m) => m.PlaygroundView));
const ProjectPlannerView = lazyView(() =>
  import('./components/backend/ProjectPlannerView').then((m) => m.ProjectPlannerView)
);
const ProjectPlanDetailView = lazyView(() =>
  import('./components/backend/ProjectPlanDetailView').then((m) => m.ProjectPlanDetailView)
);
const WebhooksManager = lazyView(() => import('./components/backend/WebhooksManager').then((m) => m.WebhooksManager));

function LoadingScreen() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-slate-950">
      <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600" />
    </div>
  );
}

function AdminViewFallback() {
  return (
    <div className="flex justify-center py-16">
      <div className="animate-spin h-10 w-10 border-b-2 border-indigo-600 rounded-full" />
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

function SettingsGroupDeepLinkRedirect() {
  const { group } = useParams<{ group: string }>();
  const target = settingsGroupPath(group?.trim() || 'general');
  return <Navigate to={target} replace />;
}

function GuestRoute({ children }: { children: React.ReactNode }) {
  const { user, pendingTwoFactor, twoFactorSetupPending } = useAuth();

  if (user && !pendingTwoFactor) {
    return <Navigate to={twoFactorSetupPending ? '/account/security' : postAuthPath(user)} replace />;
  }

  return <>{children}</>;
}

function AdminShell() {
  const { pathname } = useLocation();

  return (
    <ProtectedRoute>
      <AdminRoleGuard>
        <ResponsiveLayout>
          <AdminRouteErrorBoundary resetKey={pathname}>
            <React.Suspense fallback={<AdminViewFallback />}>
              <Outlet />
            </React.Suspense>
          </AdminRouteErrorBoundary>
        </ResponsiveLayout>
      </AdminRoleGuard>
    </ProtectedRoute>
  );
}

/** Old `/platform/*` bookmarks → same path without the section prefix. */
function LegacyPlatformRedirect() {
  const { pathname, search, hash } = useLocation();
  const rest = pathname.slice('/platform'.length);
  const next = rest === '' || rest === '/' ? ADMIN_DEFAULT_ROUTE : rest;

  return <Navigate to={`${next}${search}${hash}`} replace />;
}

function App() {
  const { loading, user, pendingTwoFactor } = useAuth();
  const { loading: setupLoading, needsSetup } = useSetupStatus();
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

  if (loading || setupLoading) {
    return <LoadingScreen />;
  }

  if (needsSetup) {
    if (location.pathname !== '/setup') {
      return <Navigate to="/setup" replace />;
    }

    return (
      <Routes>
        <Route path="/setup" element={<SetupWizardView />} />
        <Route path="*" element={<Navigate to="/setup" replace />} />
      </Routes>
    );
  }

  if (location.pathname === '/setup') {
    return <Navigate to={user && !pendingTwoFactor ? ADMIN_DEFAULT_ROUTE : '/login'} replace />;
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
      <Route path="/setup" element={<Navigate to={user && !pendingTwoFactor ? ADMIN_DEFAULT_ROUTE : '/login'} replace />} />
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
        <Route path="/editorial-calendar" element={<EditorialCalendarView />} />
        <Route path="/project-planner" element={<ProjectPlannerView />} />
        <Route path="/project-planner/:planId" element={<ProjectPlanDetailView />} />
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
        <Route path="/content-migration" element={<ContentMigrationManager />} />
        <Route path="/trash" element={<TrashManager />} />
        <Route path="/firewall" element={<FirewallManager />} />
        <Route path="/logs" element={<LogsManager />} />
        <Route path="/audit" element={<AuditTrail />} />
        <Route path="/audit/content/:contentId" element={<AuditTrail />} />
        <Route path="/audit/user/:userId" element={<AuditTrail />} />
        <Route path="/security-audit" element={<SecurityAuditManager />} />
        <Route path="/roles" element={<RolesManager />} />
        <Route path="/security/audit" element={<Navigate to="/security-audit" replace />} />
        <Route path="/security/roles" element={<Navigate to="/roles" replace />} />
        <Route path="/security/acl" element={<Navigate to="/settings?category=security&group=accessControl" replace />} />
        <Route path="/blueprints" element={<BlueprintManager />} />
        <Route path="/extensions" element={<ExtensionsManager />} />
        <Route path="/themes" element={<ThemesManager />} />
        <Route path="/themes/new" element={<ThemeStudioShell />} />
        <Route path="/themes/:themeId/edit" element={<ThemeStudioShell />} />
        <Route path="/demo" element={<DemoManager />} />
        <Route path="/notifications" element={<NotificationsOverview />} />
        <Route path="/scheduler" element={<SchedulerView />} />
        <Route path="/update" element={<SystemUpdateView />} />
        <Route path="/api-keys" element={<ApiKeysManager />} />
        <Route path="/redirects" element={<RedirectsManager />} />
        <Route path="/webhooks" element={<WebhooksManager />} />
        <Route path="/shortcodes" element={<ShortcodesManager />} />
        <Route path="/widgets" element={<WidgetsManager />} />
        <Route path="/snippets" element={<SnippetsManager />} />
        <Route path="/origin" element={<OriginPanelView />} />
        <Route path="/playground" element={<PlaygroundView />} />
        <Route path="/settings/:group" element={<SettingsGroupDeepLinkRedirect />} />
        <Route path="/settings" element={<SettingsView />} />
        <Route path="/translations" element={<TranslationEditor />} />
        <Route path="/account" element={<AccountView />} />
        <Route path="/account/public" element={<AccountView />} />
        <Route path="/account/security" element={<AccountView />} />
        <Route path="/account/preferences" element={<AccountView />} />
        <Route path="/users" element={<UsersManager />} />
        <Route path="/teams" element={<TeamsManager />} />
        <Route path="/team-chat" element={<TeamChatView />} />
        <Route path="/events" element={<EventsManager />} />
        <Route path="/time-tracker" element={<TimeTrackerView />} />
        <Route path="/kanban" element={<KanbanBoardView />} />
        <Route path="/mail" element={<MailInboxView />} />
        <Route path="/platform/*" element={<LegacyPlatformRedirect />} />
        <Route path="/developer/logs" element={<DeveloperLogsViewer />} />
      </Route>

      <Route
        path="/preview/:slug"
        element={
          <ProtectedRoute>
            <AdminRoleGuard>
              <React.Suspense fallback={<LoadingScreen />}>
                <PreviewPage />
              </React.Suspense>
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
        <Route path="*" element={<PublicNotFoundCatchAll />} />
      </Route>

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}

export default App;
