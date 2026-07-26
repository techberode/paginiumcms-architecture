import React, { useEffect, useRef } from 'react';
import {
  type ColorSchemeId,
  type ResolvedTheme,
  resolveThemeMode,
  type AppearanceMode,
} from '../../theme/colorSchemes';
import { applyColorSchemeTokens } from '../../theme/applyColorScheme';

interface SchemePreviewFrameProps {
  schemeId: ColorSchemeId;
  mode: AppearanceMode;
  className?: string;
}

export const SchemePreviewFrame: React.FC<SchemePreviewFrameProps> = ({
  schemeId,
  mode,
  className = '',
}) => {
  const rootRef = useRef<HTMLDivElement>(null);
  const resolvedTheme: ResolvedTheme = resolveThemeMode(mode);

  useEffect(() => {
    if (rootRef.current) {
      applyColorSchemeTokens(rootRef.current, schemeId, resolvedTheme);
    }
  }, [schemeId, resolvedTheme]);

  return (
    <div
      ref={rootRef}
      data-testid="scheme-preview-frame"
      className={`overflow-hidden rounded-xl border shadow-sm ${className}`}
      style={{
        backgroundColor: 'var(--color-surface)',
        color: 'var(--color-text)',
        borderColor: 'var(--color-border)',
      }}
    >
      <div
        className="px-4 py-2 text-xs font-semibold"
        style={{ backgroundColor: 'var(--color-primary)', color: 'var(--color-primary-foreground)' }}
      >
        Header · logo + nav
      </div>

      <div className="px-4 py-5 space-y-2" style={{ backgroundColor: 'var(--color-surface)' }}>
        <div className="h-3 w-2/3 rounded" style={{ backgroundColor: 'var(--color-text)' }} />
        <div className="h-2 w-1/2 rounded opacity-60" style={{ backgroundColor: 'var(--color-text-muted)' }} />
        <div
          className="mt-3 inline-block rounded-md px-3 py-1 text-[10px] font-bold"
          style={{ backgroundColor: 'var(--color-accent)', color: 'var(--color-primary-foreground)' }}
        >
          CTA
        </div>
      </div>

      <div className="grid grid-cols-2 gap-2 px-4 pb-4">
        {[0, 1].map((index) => (
          <div
            key={index}
            className="rounded-lg border p-3 space-y-2"
            style={{
              backgroundColor: 'var(--color-surface-elevated)',
              borderColor: 'var(--color-border)',
            }}
          >
            <div className="h-2 w-3/4 rounded" style={{ backgroundColor: 'var(--color-secondary)' }} />
            <div className="h-2 w-full rounded opacity-50" style={{ backgroundColor: 'var(--color-text-muted)' }} />
          </div>
        ))}
      </div>

      <div
        className="px-4 py-2 text-[10px]"
        style={{ backgroundColor: 'var(--color-secondary)', color: 'var(--color-primary-foreground)' }}
      >
        Footer
      </div>
    </div>
  );
};

export default SchemePreviewFrame;
