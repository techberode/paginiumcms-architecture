import React, { useCallback, useMemo, useRef, useState } from 'react';
import { Move } from 'lucide-react';
import { resolveContentPreviewImage } from '../../utils/contentPreviewImage';
import { MEDIA_THUMB_WIDTH } from '../../api/media';
import { useI18n } from '../../context/I18nContext';
import type { PageHeroSettings } from '../../utils/pageHero';
import { resolvePageHeroPreviewImages } from '../../utils/pageHero';

export type PageHeroPreviewLayout = 'blog-card' | 'page-header';

interface PageHeroFocusPreviewProps {
  settings: PageHeroSettings;
  seoOgImage: string;
  layout: PageHeroPreviewLayout;
  disabled?: boolean;
  onFocusChange: (focusX: number, focusY: number) => void;
}

function clampFocus(value: number): number {
  return Math.min(100, Math.max(0, Math.round(value)));
}

export const PageHeroFocusPreview: React.FC<PageHeroFocusPreviewProps> = ({
  settings,
  seoOgImage,
  layout,
  disabled = false,
  onFocusChange,
}) => {
  const { t } = useI18n();
  const [dragging, setDragging] = useState(false);
  const panRef = useRef({ focusX: 50, focusY: 50, lastX: 0, lastY: 0 });

  const images = useMemo(
    () => resolvePageHeroPreviewImages(settings, seoOgImage),
    [settings, seoOgImage]
  );
  const rawUrl = images[0] ?? '';
  const imageUrl = useMemo(
    () =>
      rawUrl === ''
        ? ''
        : resolveContentPreviewImage(
            { featuredImage: rawUrl, ogImage: rawUrl, frontMatter: {} },
            MEDIA_THUMB_WIDTH.hero
          ),
    [rawUrl]
  );

  const applyPanDelta = useCallback(
    (clientX: number, clientY: number, target: HTMLDivElement) => {
      const rect = target.getBoundingClientRect();
      if (rect.width <= 0 || rect.height <= 0) {
        return;
      }
      const dx = clientX - panRef.current.lastX;
      const dy = clientY - panRef.current.lastY;
      panRef.current.lastX = clientX;
      panRef.current.lastY = clientY;

      const deltaX = (dx / rect.width) * 100;
      const deltaY = (dy / rect.height) * 100;
      const focusX = clampFocus(panRef.current.focusX - deltaX);
      const focusY = clampFocus(panRef.current.focusY - deltaY);
      panRef.current.focusX = focusX;
      panRef.current.focusY = focusY;
      onFocusChange(focusX, focusY);
    },
    [onFocusChange]
  );

  const objectPosition = `${settings.focusX}% ${settings.focusY}%`;
  const blogCard = layout === 'blog-card';

  const frameClass = blogCard
    ? 'relative w-full aspect-[2/1] sm:aspect-[21/9] max-h-52 overflow-hidden rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-100 dark:bg-slate-900 touch-none'
    : 'relative w-full aspect-[2/1] sm:aspect-[21/9] max-h-56 overflow-hidden rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-100 dark:bg-slate-900 touch-none';

  if (imageUrl === '') {
    return (
      <div className="rounded-xl border border-dashed border-slate-300 dark:border-slate-600 px-4 py-8 text-center text-xs text-slate-500">
        {t('editor.pageHero.previewEmpty')}
      </div>
    );
  }

  const cursorClass = disabled ? 'opacity-60' : dragging ? 'cursor-grabbing' : 'cursor-grab';

  return (
    <div className="space-y-2">
      <p className="text-xs font-semibold text-slate-700 dark:text-slate-200">
        {blogCard ? t('editor.pageHero.previewBlog') : t('editor.pageHero.previewPage')}
      </p>
      <p className="text-xs text-slate-500 dark:text-slate-400">{t('editor.pageHero.previewHint')}</p>
      <div
        className={`${frameClass} ${cursorClass}`}
        role="application"
        aria-label={t('editor.pageHero.previewAria')}
        onPointerDown={(event) => {
          if (disabled) {
            return;
          }
          event.preventDefault();
          event.currentTarget.setPointerCapture(event.pointerId);
          panRef.current = {
            focusX: settings.focusX,
            focusY: settings.focusY,
            lastX: event.clientX,
            lastY: event.clientY,
          };
          setDragging(true);
        }}
        onPointerMove={(event) => {
          if (!event.currentTarget.hasPointerCapture(event.pointerId)) {
            return;
          }
          applyPanDelta(event.clientX, event.clientY, event.currentTarget);
        }}
        onPointerUp={(event) => {
          if (event.currentTarget.hasPointerCapture(event.pointerId)) {
            event.currentTarget.releasePointerCapture(event.pointerId);
          }
          setDragging(false);
        }}
        onPointerCancel={() => setDragging(false)}
      >
        <img
          src={imageUrl}
          alt=""
          className="absolute inset-0 h-full w-full object-cover pointer-events-none select-none"
          style={{ objectPosition }}
          draggable={false}
        />
        {!dragging && !disabled ? (
          <div className="pointer-events-none absolute inset-0 flex items-end justify-center pb-2 bg-gradient-to-t from-black/35 to-transparent">
            <span className="inline-flex items-center gap-1.5 rounded-full bg-black/50 px-3 py-1 text-[10px] font-semibold text-white">
              <Move className="h-3 w-3" aria-hidden />
              {t('editor.pageHero.previewDragBadge')}
            </span>
          </div>
        ) : null}
      </div>
    </div>
  );
};
