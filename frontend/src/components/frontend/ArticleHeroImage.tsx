import React from 'react';
import type { Article } from '../../api/types';
import { MEDIA_THUMB_WIDTH } from '../../api/media';
import {
  resolveContentPreviewImage,
  resolveContentPreviewSrcSet,
} from '../../utils/contentPreviewImage';
import { parseArticleHeroFocus } from '../../utils/pageHero';

type ArticleHeroSource = Pick<Article, 'title' | 'ogImage' | 'featuredImage' | 'frontMatter'>;

type Props = {
  article: ArticleHeroSource;
  /** Extra classes on the outer wrapper (e.g. `mt-8`). */
  className?: string;
};

/**
 * Public article hero under the title — same crop rules as admin drag preview.
 */
export const ArticleHeroImage: React.FC<Props> = ({ article, className = 'mt-8' }) => {
  const hero = parseArticleHeroFocus(article.frontMatter ?? {});
  const image = resolveContentPreviewImage(article, MEDIA_THUMB_WIDTH.hero);
  const imageSrcSet = resolveContentPreviewSrcSet(article, [
    MEDIA_THUMB_WIDTH.card,
    MEDIA_THUMB_WIDTH.hero,
  ]);

  if (!image) {
    return null;
  }

  const letterbox = hero.fit === 'contain';
  const objectPosition = `${hero.focusX}% ${hero.focusY}%`;

  return (
    <div
      className={`rounded-3xl overflow-hidden shadow-2xl bg-theme-surface-elevated ${className} ${
        letterbox
          ? 'max-h-[480px] flex items-center justify-center'
          : 'aspect-[21/9] max-h-[480px]'
      }`}
    >
      <img
        src={image}
        srcSet={imageSrcSet || undefined}
        sizes="(max-width: 768px) 100vw, 896px"
        alt={article.title}
        width={896}
        height={384}
        decoding="async"
        className={
          letterbox
            ? 'max-h-[480px] w-full h-auto object-contain'
            : 'w-full h-full object-cover'
        }
        style={{ objectPosition }}
      />
    </div>
  );
};

type CardProps = {
  article: ArticleHeroSource;
  /** Fixed height band from `blogCardImageHeightClass`. */
  heightClass: string;
  overlay?: React.ReactNode;
  hoverLift?: boolean;
};

/** List card thumbnail — same contain/cover + focus as detail hero. */
export const ArticleHeroCardImage: React.FC<CardProps> = ({
  article,
  heightClass,
  overlay,
  hoverLift = true,
}) => {
  const hero = parseArticleHeroFocus(article.frontMatter ?? {});
  const image = resolveContentPreviewImage(article, MEDIA_THUMB_WIDTH.card);
  const imageSrcSet = resolveContentPreviewSrcSet(article, [
    MEDIA_THUMB_WIDTH.card,
    MEDIA_THUMB_WIDTH.hero,
  ]);

  if (!image) {
    return <div className={`${heightClass} relative bg-theme-surface`}>{overlay}</div>;
  }

  const letterbox = hero.fit === 'contain';
  const objectPosition = `${hero.focusX}% ${hero.focusY}%`;
  const hoverClass = hoverLift
    ? letterbox
      ? 'group-hover:scale-[1.02]'
      : 'group-hover:scale-105'
    : '';

  return (
    <div
      className={`${heightClass} overflow-hidden relative bg-theme-surface ${
        letterbox ? 'flex items-center justify-center' : ''
      }`}
    >
      <img
        src={image}
        srcSet={imageSrcSet || undefined}
        sizes="(max-width: 768px) 100vw, 400px"
        alt={article.title}
        loading="lazy"
        decoding="async"
        className={
          letterbox
            ? `max-w-full max-h-full w-full h-full object-contain transition-transform duration-500 ${hoverClass}`
            : `w-full h-full object-cover transition-transform duration-500 ${hoverClass}`
        }
        style={{ objectPosition }}
      />
      {overlay}
    </div>
  );
};
