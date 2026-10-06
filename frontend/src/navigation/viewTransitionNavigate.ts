import type { NavigateFunction, NavigateOptions } from 'react-router-dom';

export function supportsViewTransition(): boolean {
  return typeof document !== 'undefined' && typeof document.startViewTransition === 'function';
}

export function navigateWithViewTransition(
  navigate: NavigateFunction,
  to: string,
  options?: NavigateOptions,
  enabled = true
): void {
  if (!enabled || !supportsViewTransition()) {
    navigate(to, options);
    return;
  }

  document.startViewTransition(() => {
    navigate(to, options);
  });
}
