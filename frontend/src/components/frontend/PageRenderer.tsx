// frontend/src/components/frontend/PageRenderer.tsx
import React, { useMemo, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { Page } from '../../api/types';
import { ContactForm } from './ContactForm';
import { CompanyInfoPanel, CompanyMapEmbed } from './CompanyInfoPanel';
import { ContentShareBar } from './ContentShareBar';
import { StaffDirectory } from './StaffDirectory';
import { MarkdownRenderer } from '../common/MarkdownRenderer';
import { PageLayoutShell } from '../../layout/PageLayoutShell';
import { normalizePageLayoutTemplateId } from '../../layout/pageLayoutTemplates';
import { ArrowRight } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import { formatDisplayDate, resolveContentDate } from '../../utils/contentDates';
import { BTN_PRIMARY, PUBLIC_CARD } from '../../theme/publicUiClasses';
import { useLandingReveal } from '../../hooks/useLandingReveal';
import { ComingSoonCountdown } from './ComingSoonCountdown';
import { PageHeroCover } from './PageHeroCover';
import { PageHeroHeader } from './PageHeroHeader';
import { PageHeroMedia } from './PageHeroMedia';
import { resolveContentPreviewImage } from '../../utils/contentPreviewImage';
import { MEDIA_THUMB_WIDTH } from '../../api/media';
import {
  parsePageHeroSettings,
  resolvePageHero,
  resolvePageHeroRenderFlags,
  stripHeroDuplicateFromBody,
  stripHeroDuplicateFromHtml,
} from '../../utils/pageHero';
import { isHomeTemplatePage, landingHeroPresentInBody } from '../../utils/siteHomePage';

export type PageRendererVariant = 'full' | 'embed';

interface PageRendererProps {
  page: Page;
  /** embed = blog intro / partial chrome — no min-height shell, tighter landing heroes */
  variant?: PageRendererVariant;
}

function pageMeta(page: Page, defaultAuthor: string) {
  const fm = page.frontMatter ?? {};
  return {
    template: String(page.template ?? fm.template ?? ''),
    layoutTemplate: normalizePageLayoutTemplateId(
      String(page.layoutTemplate ?? fm.layoutTemplate ?? '')
    ),
    description: String(fm.description ?? fm.seoDescription ?? ''),
    date: resolveContentDate(fm.date, page.createdAt),
    author: String(page.author ?? fm.author ?? defaultAuthor),
  };
}

export const PageRenderer: React.FC<PageRendererProps> = ({ page, variant = 'full' }) => {
  const embed = variant === 'embed';
  const { t, locale } = useI18n();
  const navigate = useNavigate();
  const meta = pageMeta(page, t('public.defaults.editorial'));
  const heroSettings = useMemo(() => parsePageHeroSettings(page), [page]);
  const resolvedHero = useMemo(() => resolvePageHero(page, heroSettings), [page, heroSettings]);
  const isHome = isHomeTemplatePage(page);
  const isContact = meta.template === 'contact' || page.slug === 'contact';
  const isServices = meta.template === 'services' || page.slug === 'sluzby' || page.slug === 'services';
  const isLandingLayout = meta.layoutTemplate === 'landing';

  const heroCtx = { embed, isHome, isLandingLayout };
  const heroFlags = resolvePageHeroRenderFlags(heroSettings, heroCtx);
  const heroPlacement = heroFlags.placement;

  const bodyForDisplay = useMemo(
    () => stripHeroDuplicateFromBody(page.content ?? '', resolvedHero.images),
    [page.content, resolvedHero.images]
  );
  const htmlForDisplay = useMemo(() => {
    if (!page.html) {
      return undefined;
    }
    const stripped = stripHeroDuplicateFromHtml(page.html, resolvedHero.images);
    return stripped === '' ? undefined : stripped;
  }, [page.html, resolvedHero.images]);

  const landingContentRef = useRef<HTMLDivElement>(null);
  const useLandingShell = heroFlags.landingInlineShell;
  useLandingReveal(landingContentRef, useLandingShell && !embed);

  const templateLabel = meta.template ? meta.template.toUpperCase() : t('public.page.meta.pageLabel');
  const dateLabel = formatDisplayDate(meta.date, locale);
  const showHeroMedia = resolvedHero.showImage && heroPlacement !== 'none';

  const introCardHero =
    showHeroMedia && heroFlags.introCard ? (
      <PageHeroMedia
        variant="embed"
        title={page.title}
        images={resolvedHero.images}
        focusX={resolvedHero.focusX}
        focusY={resolvedHero.focusY}
        carousel={resolvedHero.mode === 'carousel'}
        className="mb-6 sm:mb-8"
      />
    ) : null;

  const homeMarketingHero =
    isHome && !embed && heroFlags.headerBand && !showHeroMedia ? (
      <div className="relative overflow-hidden public-hero pt-20 pb-28 min-h-[22rem]">
        <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
          <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-theme-primary/20 text-theme-primary-foreground font-bold text-xs mb-8 border border-theme-primary/30 backdrop-blur-md">
            <span>{t('public.page.hero.badge')}</span>
          </div>
          <h1 className="text-4xl sm:text-6xl font-extrabold tracking-tight leading-tight max-w-4xl mx-auto">
            {page.title}
          </h1>
          <p className="mt-6 text-lg sm:text-xl opacity-90 max-w-2xl mx-auto font-normal leading-relaxed">
            {meta.description}
          </p>
          <div className="mt-10 flex flex-wrap justify-center gap-4">
            <button
              type="button"
              onClick={() => navigate('/blog')}
              className={`${BTN_PRIMARY} px-8 py-4 rounded-2xl shadow-xl flex items-center gap-2 cursor-pointer text-base group`}
            >
              <span>{t('public.page.hero.exploreBlog')}</span>
              <ArrowRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
            </button>
            <button
              type="button"
              onClick={() => navigate('/about')}
              className="bg-theme-text/20 hover:bg-theme-text/30 text-theme-primary-foreground font-bold px-8 py-4 rounded-2xl border border-theme-primary-foreground/20 backdrop-blur-md transition-all cursor-pointer text-base"
            >
              {t('public.page.hero.aboutUs')}
            </button>
          </div>
        </div>
      </div>
    ) : null;

  const heroCoverProps = {
    title: page.title,
    description: meta.description,
    images: resolvedHero.images,
    focusX: resolvedHero.focusX,
    focusY: resolvedHero.focusY,
    carousel: resolvedHero.mode === 'carousel',
  };

  const fullHeaderCover =
    !embed && heroFlags.headerBand && showHeroMedia ? (
      <PageHeroCover
        {...heroCoverProps}
        templateLabel={isHome ? undefined : templateLabel}
        dateLabel={isHome ? undefined : dateLabel}
        authorLabel={isHome ? undefined : meta.author}
      />
    ) : null;

  const standardPageHeader =
    !embed && heroFlags.headerBand && !isHome && !showHeroMedia ? (
      <PageHeroHeader
        variant="full"
        title={page.title}
        description={meta.description}
        templateLabel={templateLabel}
        dateLabel={dateLabel}
        authorLabel={meta.author}
        images={[]}
        focusX={resolvedHero.focusX}
        focusY={resolvedHero.focusY}
        showMedia={false}
      />
    ) : null;

  const standardPageCover =
    !embed && heroFlags.headerBand && !isHome && showHeroMedia ? (
      <PageHeroCover
        {...heroCoverProps}
        templateLabel={templateLabel}
        dateLabel={dateLabel}
        authorLabel={meta.author}
      />
    ) : null;

  const embedHeaderCover =
    embed && heroFlags.headerBand && showHeroMedia ? (
      <PageHeroCover {...heroCoverProps} breakout />
    ) : null;

  const embedTitleHeader =
    embed && heroFlags.headerBand && !showHeroMedia ? (
      <PageHeroHeader
        variant="embed"
        title={page.title}
        description={meta.description}
        images={[]}
        focusX={resolvedHero.focusX}
        focusY={resolvedHero.focusY}
        showMedia={false}
      />
    ) : null;

  const introTitleHeader =
    heroFlags.introCard ? (
      <PageHeroHeader
        variant="embed"
        title={page.title}
        description={meta.description}
        images={[]}
        focusX={resolvedHero.focusX}
        focusY={resolvedHero.focusY}
        showMedia={false}
      />
    ) : null;

  const landingHeroBackground =
    useLandingShell && showHeroMedia && resolvedHero.images[0]
      ? `url("${resolveContentPreviewImage(
          { featuredImage: resolvedHero.images[0], ogImage: resolvedHero.images[0], frontMatter: {} },
          MEDIA_THUMB_WIDTH.hero
        )}")`
      : undefined;

  const hasBodyContent =
    bodyForDisplay.trim() !== '' || (htmlForDisplay !== undefined && htmlForDisplay.trim() !== '');

  const landingNeedsSeoHeroFallback =
    useLandingShell &&
    showHeroMedia &&
    !landingHeroPresentInBody(htmlForDisplay ?? page.html, bodyForDisplay);

  const contentBlock = useLandingShell ? (
    <div
      ref={landingContentRef}
      className="pg-landing-content paginium-prose max-w-none"
      data-has-hero-image={showHeroMedia ? 'true' : 'false'}
      style={
        landingHeroBackground
          ? ({
              ['--pg-hero-image' as string]: landingHeroBackground,
              ['--pg-hero-pos-x' as string]: `${resolvedHero.focusX}%`,
              ['--pg-hero-pos-y' as string]: `${resolvedHero.focusY}%`,
            } as React.CSSProperties)
          : undefined
      }
    >
      {landingNeedsSeoHeroFallback ? (
        <div className="pg-landing-seo-hero">
          <PageHeroMedia
            variant="full"
            title={page.title}
            images={resolvedHero.images}
            focusX={resolvedHero.focusX}
            focusY={resolvedHero.focusY}
            carousel={resolvedHero.mode === 'carousel'}
          />
        </div>
      ) : null}
      <MarkdownRenderer content={bodyForDisplay} html={htmlForDisplay} enableImageLightbox />
    </div>
  ) : heroFlags.introCard && (introCardHero || hasBodyContent) ? (
    <div className={`${PUBLIC_CARD} p-8 sm:p-12`}>
      {introCardHero}
      {hasBodyContent ? (
        <MarkdownRenderer content={bodyForDisplay} html={htmlForDisplay} enableImageLightbox />
      ) : null}
    </div>
  ) : heroFlags.headerBand && hasBodyContent ? (
    <div className={`${PUBLIC_CARD} p-8 sm:p-12`}>
      <MarkdownRenderer content={bodyForDisplay} html={htmlForDisplay} enableImageLightbox />
    </div>
  ) : hasBodyContent ? (
    <div className={`${PUBLIC_CARD} p-8 sm:p-12`}>
      <MarkdownRenderer content={bodyForDisplay} html={htmlForDisplay} enableImageLightbox />
    </div>
  ) : introCardHero ? (
    <div className={`${PUBLIC_CARD} p-8 sm:p-12`}>{introCardHero}</div>
  ) : null;

  const rootClass = embed
    ? 'bg-transparent text-theme-text pb-0 transition-colors pg-page-embed'
    : 'min-h-screen bg-theme-surface text-theme-text pb-12 sm:pb-16 transition-colors';

  const mainClass = embed
    ? 'mx-auto px-0 sm:px-0 max-w-none mt-0'
    : `mx-auto px-4 sm:px-6 lg:px-8 ${useLandingShell ? 'max-w-6xl mt-2 sm:mt-4' : 'max-w-4xl mt-6 sm:mt-8'}`;

  const topHeader = embed
    ? heroFlags.introCard
      ? introTitleHeader
      : heroFlags.headerBand && showHeroMedia
        ? embedHeaderCover
        : heroFlags.headerBand
          ? embedTitleHeader
          : null
    : isHome && heroFlags.headerBand && showHeroMedia
      ? fullHeaderCover
      : isHome && heroFlags.headerBand && !showHeroMedia
        ? homeMarketingHero
        : !isHome && heroFlags.headerBand && showHeroMedia
          ? standardPageCover
          : !isHome && heroFlags.headerBand
            ? standardPageHeader
            : heroFlags.introCard
              ? introTitleHeader
              : null;

  return (
    <div className={rootClass} data-page-variant={variant} data-hero-placement={heroPlacement}>
      <main className={mainClass}>
        <ComingSoonCountdown kind="page" slug={page.slug} />
        {topHeader}

        {isHome && !useLandingShell && !embed && heroFlags.headerBand ? (
          contentBlock
        ) : (
          <PageLayoutShell layoutTemplate={meta.layoutTemplate} hero={undefined}>
            {contentBlock}
            {!embed ? <ContentShareBar title={page.title} surface="page" isHome={isHome} /> : null}
          </PageLayoutShell>
        )}

        {isContact && !embed && (
          <div className="mt-12 space-y-8">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
              <CompanyInfoPanel />
              <ContactForm />
            </div>
            <StaffDirectory />
            <CompanyMapEmbed />
          </div>
        )}

        {isServices && !embed && (
          <div
            className="mt-12 rounded-3xl p-8 sm:p-12 text-theme-primary-foreground shadow-xl text-center"
            style={{
              background: 'linear-gradient(to right, var(--color-primary), var(--color-accent))',
            }}
          >
            <h3 className="text-2xl font-black">{t('public.page.services.ctaTitle')}</h3>
            <p className="mt-3 opacity-90 max-w-xl mx-auto text-sm">{t('public.page.services.ctaBody')}</p>
            <button
              type="button"
              onClick={() => navigate('/contact')}
              className="mt-6 bg-theme-surface-elevated hover:opacity-90 text-theme-text font-extrabold px-8 py-3.5 rounded-xl shadow transition-all cursor-pointer text-sm"
            >
              {t('public.page.services.ctaButton')}
            </button>
          </div>
        )}
      </main>
    </div>
  );
};

export default PageRenderer;
