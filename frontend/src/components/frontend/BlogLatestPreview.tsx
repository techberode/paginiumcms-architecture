import React from 'react';
import { Calendar, Clock, Sparkles } from 'lucide-react';
import type { Article } from '../../api/types';
import { formatContentDateLabels } from '../../utils/contentDates';
import { formatReadingTime } from '../../utils/readingTime';
import { ArticleHeroCardImage } from './ArticleHeroImage';
import { blogCardImageHeightClass } from '../../utils/blogListLayout';
import { buildArticleTeaserCollapsed } from '../../utils/articleTeaser';
import { BTN_PRIMARY, PUBLIC_CARD } from '../../theme/publicUiClasses';

type BlogLatestPreviewProps = {
  article: Article;
  locale: string;
  showReadingTime: boolean;
  readMoreLabel: string;
  latestLabel: string;
  archiveCategoryLabel: string;
  onOpenArticle: () => void;
};

export const BlogLatestPreview: React.FC<BlogLatestPreviewProps> = ({
  article,
  locale,
  showReadingTime,
  readMoreLabel,
  latestLabel,
  archiveCategoryLabel,
  onOpenArticle,
}) => {
  const teaser = buildArticleTeaserCollapsed(article);
  const isArchive = article.category?.trim().toLowerCase() === 'archive';
  const dates = formatContentDateLabels(
    {
      createdAt: article.createdAt,
      updatedAt: article.updatedAt,
      frontMatterDate: article.frontMatter?.date as string | number | undefined,
    },
    locale
  );

  return (
    <article className={`mb-8 sm:mb-10 ${PUBLIC_CARD} overflow-hidden shadow-lg border-theme-primary/20`}>
      <div className="relative">
        <ArticleHeroCardImage
          article={article}
          heightClass={blogCardImageHeightClass('comfortable')}
          overlay={
            <span className="absolute top-4 left-4 inline-flex items-center gap-1.5 rounded-lg bg-theme-primary px-3 py-1.5 text-[11px] font-extrabold uppercase tracking-wide text-theme-primary-foreground shadow-md">
              <Sparkles className="w-3.5 h-3.5" />
              {latestLabel}
            </span>
          }
        />
      </div>
      <div className="p-5 sm:p-6 text-left space-y-4">
        <div className="flex flex-wrap items-center gap-2 text-xs text-theme-text-muted font-medium">
          <span className="inline-flex items-center gap-1 rounded-full bg-theme-surface px-2.5 py-1" title={dates.primaryTitle}>
            <Calendar className="w-3.5 h-3.5 text-theme-primary" />
            {dates.primary}
          </span>
          {showReadingTime && article.readingTime > 0 && (
            <span className="inline-flex items-center gap-1 rounded-full bg-theme-primary/10 px-2.5 py-1 text-theme-primary font-bold">
              <Clock className="w-3.5 h-3.5" />
              {formatReadingTime(article.readingTime, locale)}
            </span>
          )}
          {isArchive ? (
            <span className="inline-flex items-center rounded-full bg-slate-500/15 px-2.5 py-1 text-slate-700 dark:text-slate-300 font-bold">
              {archiveCategoryLabel}
            </span>
          ) : null}
        </div>
        <h2 className="text-2xl sm:text-3xl font-black tracking-tight text-theme-text leading-snug">{article.title}</h2>
        {teaser ? (
          <p className="text-theme-text-muted text-sm sm:text-base leading-relaxed line-clamp-5">{teaser}</p>
        ) : null}
        <button type="button" onClick={onOpenArticle} className={`${BTN_PRIMARY} px-6 py-2.5 text-sm font-bold`}>
          {readMoreLabel}
        </button>
      </div>
    </article>
  );
};
