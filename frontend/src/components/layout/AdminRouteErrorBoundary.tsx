import React from 'react';
import { useI18n } from '../../context/I18nContext';

type Props = {
  children: React.ReactNode;
  /** Changing this (e.g. the pathname) clears a previous error. */
  resetKey: string;
};

type State = { failedKey: string | null };

function AdminViewLoadFailed() {
  const { t } = useI18n();

  return (
    <div className="p-6 lg:p-10 max-w-3xl mx-auto">
      <div
        role="alert"
        className="rounded-2xl border border-rose-200 bg-rose-50 p-6 text-sm text-rose-700 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300"
      >
        <p>{t('common.viewLoadFailed')}</p>
        <button
          type="button"
          onClick={() => window.location.reload()}
          className="mt-4 rounded-xl bg-rose-600 px-4 py-2 font-semibold text-white hover:bg-rose-700"
        >
          {t('common.reload')}
        </button>
      </div>
    </div>
  );
}

/** Catches lazy admin view failures (stale chunk after deploy, render errors) per route. */
export class AdminRouteErrorBoundary extends React.Component<Props, State> {
  state: State = { failedKey: null };

  static getDerivedStateFromError(): Partial<State> {
    return { failedKey: '__pending__' };
  }

  componentDidCatch(error: unknown): void {
    this.setState({ failedKey: this.props.resetKey });
    if (import.meta.env.DEV) {
      console.error('[AdminRouteErrorBoundary]', error);
    }
  }

  render(): React.ReactNode {
    const { failedKey } = this.state;
    if (failedKey !== null && (failedKey === '__pending__' || failedKey === this.props.resetKey)) {
      return <AdminViewLoadFailed />;
    }

    return this.props.children;
  }
}
