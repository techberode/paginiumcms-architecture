import React from 'react';
import { Calendar, FileText, User } from 'lucide-react';
import { PageHeroMedia } from './PageHeroMedia';

export interface PageHeroHeaderProps {
  variant?: 'full' | 'embed';
  title: string;
  description?: string;
  templateLabel?: string;
  dateLabel?: string;
  authorLabel?: string;
  images: string[];
  focusX: number;
  focusY: number;
  carousel?: boolean;
  /** When false, title/description only (hero renders elsewhere, e.g. blog greybox). */
  showMedia?: boolean;
}

export const PageHeroHeader: React.FC<PageHeroHeaderProps> = ({
  variant = 'full',
  title,
  description = '',
  templateLabel,
  dateLabel,
  authorLabel,
  images,
  focusX,
  focusY,
  carousel = false,
  showMedia = true,
}) => {
  const embed = variant === 'embed';
  const showImage = showMedia && images.length > 0;

  return (
    <header className={embed ? 'mb-4 text-left' : 'bg-theme-surface-elevated border-b border-theme-border pt-6 pb-8 sm:pt-8 sm:pb-10'}>
      <div className={embed ? 'space-y-3' : 'max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4'}>
        {!embed && templateLabel ? (
          <div className="flex items-center gap-3 text-xs text-theme-text-muted font-semibold">
            <span className="flex items-center gap-1 text-theme-primary">
              <FileText className="w-4 h-4" />
              {templateLabel}
            </span>
            {dateLabel ? (
              <>
                <span>•</span>
                <span className="flex items-center gap-1">
                  <Calendar className="w-3.5 h-3.5" />
                  {dateLabel}
                </span>
              </>
            ) : null}
            {authorLabel ? (
              <>
                <span>•</span>
                <span className="flex items-center gap-1">
                  <User className="w-3.5 h-3.5" />
                  {authorLabel}
                </span>
              </>
            ) : null}
          </div>
        ) : null}

        <h1 className={`font-extrabold tracking-tight text-theme-text ${embed ? 'text-2xl sm:text-3xl' : 'text-3xl sm:text-5xl'}`}>
          {title}
        </h1>
        {description.trim() !== '' ? (
          <p className={`text-theme-text-muted leading-relaxed ${embed ? 'text-sm sm:text-base' : 'text-base sm:text-lg max-w-3xl'}`}>
            {description}
          </p>
        ) : null}

        {showImage ? (
          <PageHeroMedia
            variant={variant}
            title={title}
            images={images}
            focusX={focusX}
            focusY={focusY}
            carousel={carousel}
          />
        ) : null}
      </div>
    </header>
  );
};
