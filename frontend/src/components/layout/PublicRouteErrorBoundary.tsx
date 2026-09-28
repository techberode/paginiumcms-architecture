import React from 'react';
import { PublicSystemErrorPanel } from '../frontend/PublicSystemErrorPanel';
import { PageRenderer } from '../frontend/PageRenderer';
import type { Page } from '../../api/types';

type Props = {
  children: React.ReactNode;
  errorPage?: Page;
};

type State = { hasError: boolean };

export class PublicRouteErrorBoundary extends React.Component<Props, State> {
  state: State = { hasError: false };

  static getDerivedStateFromError(): State {
    return { hasError: true };
  }

  componentDidCatch(error: unknown): void {
    if (import.meta.env.DEV) {
      console.error('[PublicRouteErrorBoundary]', error);
    }
  }

  render(): React.ReactNode {
    if (!this.state.hasError) {
      return this.props.children;
    }

    if (this.props.errorPage) {
      return <PageRenderer page={this.props.errorPage} />;
    }

    return <PublicSystemErrorPanel kind="serverError" />;
  }
}
