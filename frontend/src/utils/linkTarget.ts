export interface LinkTargetProps {
  target?: '_blank';
  rel?: string;
}

/** Props for React Router Link / anchor when opening in a new tab is optional. */
export function linkTargetProps(openInNewTab: boolean): LinkTargetProps {
  if (!openInNewTab) {
    return {};
  }
  return { target: '_blank', rel: 'noopener noreferrer' };
}

/** Navigate to an external or absolute URL (same tab or new tab). */
export function openExternalUrl(url: string, openInNewTab: boolean): void {
  if (openInNewTab) {
    window.open(url, '_blank', 'noopener,noreferrer');
    return;
  }
  window.location.assign(url);
}
