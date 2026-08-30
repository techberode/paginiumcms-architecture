import React from 'react';

interface ThemeShellBoundaryProps {
  children: React.ReactNode;
  fallback: React.ReactNode;
  themeId: string;
  onShellError?: (themeId: string) => void;
}

interface ThemeShellBoundaryState {
  failed: boolean;
}

/**
 * Fail-safe wrapper: if a theme shell throws during render, fall back to core chrome.
 */
export class ThemeShellBoundary extends React.Component<
  ThemeShellBoundaryProps,
  ThemeShellBoundaryState
> {
  state: ThemeShellBoundaryState = { failed: false };

  static getDerivedStateFromError(): ThemeShellBoundaryState {
    return { failed: true };
  }

  componentDidCatch(error: Error): void {
    console.error('[PaginiumCMS] Theme shell render failed:', this.props.themeId, error);
    this.props.onShellError?.(this.props.themeId);
  }

  render(): React.ReactNode {
    if (this.state.failed) {
      return this.props.fallback;
    }

    return this.props.children;
  }
}

export default ThemeShellBoundary;
