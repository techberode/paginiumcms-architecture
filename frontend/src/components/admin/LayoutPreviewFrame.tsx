import React, { useEffect, useRef } from 'react';
import {
  type AppearanceMode,
  type ColorSchemeId,
  type ResolvedTheme,
  resolveThemeMode,
} from '../../theme/colorSchemes';
import { applyColorSchemeTokens } from '../../theme/applyColorScheme';
import {
  type PageLayoutTemplateId,
  normalizePageLayoutTemplateId,
} from '../../layout/pageLayoutTemplates';

interface LayoutPreviewFrameProps {
  templateId: PageLayoutTemplateId | string;
  schemeId?: ColorSchemeId;
  mode?: AppearanceMode;
  className?: string;
}

const WireBar: React.FC<{ widthClass: string; muted?: boolean }> = ({ widthClass, muted }) => (
  <div
    className={`h-2 rounded ${widthClass} ${muted ? 'opacity-50' : ''}`}
    style={{ backgroundColor: muted ? 'var(--color-text-muted)' : 'var(--color-text)' }}
  />
);

const CardSlot: React.FC = () => (
  <div
    className="rounded-lg border p-3 space-y-2"
    style={{
      backgroundColor: 'var(--color-surface-elevated)',
      borderColor: 'var(--color-border)',
    }}
  >
    <div className="h-2 w-3/4 rounded" style={{ backgroundColor: 'var(--color-secondary)' }} />
    <div className="h-2 w-full rounded opacity-50" style={{ backgroundColor: 'var(--color-text-muted)' }} />
  </div>
);

function TemplateBody({ templateId }: { templateId: PageLayoutTemplateId }): React.ReactElement {
  switch (templateId) {
    case 'single':
      return (
        <div className="px-4 py-5 space-y-2" data-testid="layout-slot-body">
          <WireBar widthClass="w-3/4" />
          <WireBar widthClass="w-full" muted />
          <WireBar widthClass="w-5/6" muted />
        </div>
      );
    case 'two-column':
      return (
        <div className="grid grid-cols-3 gap-2 px-4 py-5" data-testid="layout-slot-columns">
          <div className="col-span-2 space-y-2">
            <WireBar widthClass="w-3/4" />
            <WireBar widthClass="w-full" muted />
            <WireBar widthClass="w-2/3" muted />
          </div>
          <div className="space-y-2">
            <CardSlot />
          </div>
        </div>
      );
    case 'landing':
      return (
        <div className="space-y-3 px-4 py-5" data-testid="layout-slot-landing">
          <div className="space-y-2">
            <WireBar widthClass="w-2/3" />
            <WireBar widthClass="w-1/2" muted />
            <div
              className="mt-2 inline-block rounded-md px-3 py-1 text-[10px] font-bold"
              style={{ backgroundColor: 'var(--color-accent)', color: 'var(--color-primary-foreground)' }}
            >
              CTA
            </div>
          </div>
          <div className="grid grid-cols-3 gap-2">
            {[0, 1, 2].map((index) => (
              <CardSlot key={index} />
            ))}
          </div>
          <div
            className="rounded-md px-3 py-2 text-center text-[10px] font-semibold"
            style={{ backgroundColor: 'var(--color-primary)', color: 'var(--color-primary-foreground)' }}
          >
            Bottom CTA
          </div>
        </div>
      );
    case 'blog-article':
      return (
        <div className="px-4 py-5 space-y-3" data-testid="layout-slot-article">
          <WireBar widthClass="w-4/5" />
          <div className="h-1.5 w-1/3 rounded opacity-40" style={{ backgroundColor: 'var(--color-text-muted)' }} />
          <WireBar widthClass="w-full" muted />
          <WireBar widthClass="w-full" muted />
          <WireBar widthClass="w-3/4" muted />
        </div>
      );
    case 'hero-content':
    default:
      return (
        <>
          <div className="px-4 py-5 space-y-2" data-testid="layout-slot-hero">
            <WireBar widthClass="w-2/3" />
            <WireBar widthClass="w-1/2" muted />
            <div
              className="mt-3 inline-block rounded-md px-3 py-1 text-[10px] font-bold"
              style={{ backgroundColor: 'var(--color-accent)', color: 'var(--color-primary-foreground)' }}
            >
              CTA
            </div>
          </div>
          <div className="grid grid-cols-2 gap-2 px-4 pb-4">
            {[0, 1].map((index) => (
              <CardSlot key={index} />
            ))}
          </div>
        </>
      );
  }
}

export const LayoutPreviewFrame: React.FC<LayoutPreviewFrameProps> = ({
  templateId,
  schemeId,
  mode = 'system',
  className = '',
}) => {
  const rootRef = useRef<HTMLDivElement>(null);
  const safeTemplate = normalizePageLayoutTemplateId(templateId);
  const resolvedTheme: ResolvedTheme = resolveThemeMode(mode);

  useEffect(() => {
    if (rootRef.current && schemeId) {
      applyColorSchemeTokens(rootRef.current, schemeId, resolvedTheme);
    }
  }, [schemeId, resolvedTheme]);

  return (
    <div
      ref={rootRef}
      data-testid="layout-preview-frame"
      data-layout-template={safeTemplate}
      className={`overflow-hidden rounded-xl border shadow-sm ${className}`}
      style={{
        backgroundColor: 'var(--color-surface, #f8fafc)',
        color: 'var(--color-text, #0f172a)',
        borderColor: 'var(--color-border, #e2e8f0)',
      }}
    >
      <div
        className="px-4 py-2 text-xs font-semibold"
        style={{
          backgroundColor: 'var(--color-primary, #4f46e5)',
          color: 'var(--color-primary-foreground, #ffffff)',
        }}
      >
        Header · logo + nav
      </div>

      <TemplateBody templateId={safeTemplate} />

      <div
        className="px-4 py-2 text-[10px]"
        style={{
          backgroundColor: 'var(--color-secondary, #64748b)',
          color: 'var(--color-primary-foreground, #ffffff)',
        }}
      >
        Footer
      </div>
    </div>
  );
};

export default LayoutPreviewFrame;
