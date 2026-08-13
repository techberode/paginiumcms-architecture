// frontend/src/components/frontend/PageRenderer.tsx
import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Page } from '../../api/types';
import { ContactForm } from './ContactForm';
import { CompanyInfoPanel, CompanyMapEmbed } from './CompanyInfoPanel';
import { MarkdownRenderer } from '../common/MarkdownRenderer';
import { PageLayoutShell } from '../../layout/PageLayoutShell';
import { normalizePageLayoutTemplateId } from '../../layout/pageLayoutTemplates';
import { Calendar, User, FileText, ArrowRight } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import { formatDisplayDate, resolveContentDate } from '../../utils/contentDates';
import { BTN_PRIMARY, PUBLIC_CARD } from '../../theme/publicUiClasses';

interface PageRendererProps {
  page: Page;
}

function pageMeta(page: Page, defaultAuthor: string) {
  const fm = page.frontMatter ?? {};
  return {
    template: String(page.template ?? fm.template ?? ''),
    layoutTemplate: normalizePageLayoutTemplateId(
      String(page.layoutTemplate ?? fm.layoutTemplate ?? '')
    ),
    description: String(fm.description ?? ''),
    featuredImage: String(fm.featuredImage ?? fm.featured_image ?? ''),
    date: resolveContentDate(fm.date, page.createdAt),
    author: String(page.author ?? fm.author ?? defaultAuthor),
  };
}

export const PageRenderer: React.FC<PageRendererProps> = ({ page }) => {
  const { t, locale } = useI18n();
  const navigate = useNavigate();
  const meta = pageMeta(page, t('public.defaults.editorial'));
  const isHome = meta.template === 'home' || page.slug === 'home';
  const isContact = meta.template === 'contact' || page.slug === 'contact';
  const isServices = meta.template === 'services' || page.slug === 'sluzby' || page.slug === 'services';
  const isAbout = meta.template === 'about' || page.slug === 'about';

  const heroBlock = isHome ? (
    <div className="relative overflow-hidden public-hero pt-20 pb-28">
      <div className="absolute inset-0 z-0 opacity-20">
        {meta.featuredImage && (
          <img src={meta.featuredImage} alt={t('public.page.hero.imageAlt')} className="w-full h-full object-cover" />
        )}
        <div className="absolute inset-0 bg-theme-text/80 backdrop-blur-sm" />
      </div>
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
  ) : (
    <div className="bg-theme-surface-elevated border-b border-theme-border pt-12 pb-16">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center gap-3 text-xs text-theme-text-muted font-semibold mb-3">
          <span className="flex items-center gap-1 text-theme-primary">
            <FileText className="w-4 h-4" />
            {meta.template ? meta.template.toUpperCase() : t('public.page.meta.pageLabel')}
          </span>
          <span>•</span>
          <span className="flex items-center gap-1">
            <Calendar className="w-3.5 h-3.5" />
            {formatDisplayDate(meta.date, locale)}
          </span>
          <span>•</span>
          <span className="flex items-center gap-1">
            <User className="w-3.5 h-3.5" />
            {meta.author}
          </span>
        </div>
        <h1 className="text-3xl sm:text-5xl font-extrabold tracking-tight text-theme-text">
          {page.title}
        </h1>
        <p className="mt-4 text-base sm:text-lg text-theme-text-muted font-normal leading-relaxed max-w-3xl">
          {meta.description}
        </p>
        {meta.featuredImage && !isAbout && !isServices && (
          <div className="mt-8 rounded-3xl overflow-hidden shadow-xl max-h-[400px]">
            <img src={meta.featuredImage} alt={page.title} className="w-full h-full object-cover" />
          </div>
        )}
      </div>
    </div>
  );

  const contentBlock = (
    <div className={`${PUBLIC_CARD} p-8 sm:p-12`}>
      <MarkdownRenderer content={page.content} html={page.html} />
    </div>
  );

  return (
    <div className="min-h-screen bg-theme-surface text-theme-text pb-20 transition-colors">
      {meta.layoutTemplate === 'hero-content' || isHome ? heroBlock : null}

      <main className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
        <PageLayoutShell
          layoutTemplate={meta.layoutTemplate}
          hero={meta.layoutTemplate !== 'hero-content' && !isHome ? heroBlock : undefined}
        >
          {contentBlock}
        </PageLayoutShell>

        {isContact && (
          <div className="mt-12 space-y-8">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
              <CompanyInfoPanel />
              <ContactForm />
            </div>
            <CompanyMapEmbed />
          </div>
        )}

        {isServices && (
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
