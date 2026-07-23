// frontend/src/components/frontend/PageRenderer.tsx
import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Page } from '../../api/types';
import { ContactForm } from './ContactForm';
import { CompanyInfoPanel, CompanyMapEmbed } from './CompanyInfoPanel';
import { MarkdownRenderer } from '../common/MarkdownRenderer';
import { Calendar, User, FileText, ArrowRight } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import { formatDisplayDate, resolveContentDate } from '../../utils/contentDates';

interface PageRendererProps {
  page: Page;
}

function pageMeta(page: Page, defaultAuthor: string) {
  const fm = page.frontMatter ?? {};
  return {
    template: String(page.template ?? fm.template ?? ''),
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

  return (
    <div className="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 pb-20 transition-colors">
      {isHome ? (
        <div className="relative overflow-hidden bg-gradient-to-b from-slate-900 via-indigo-950 to-slate-900 text-white pt-20 pb-28 border-b border-slate-800">
          <div className="absolute inset-0 z-0 opacity-20">
            {meta.featuredImage && (
              <img src={meta.featuredImage} alt={t('public.page.hero.imageAlt')} className="w-full h-full object-cover" />
            )}
            <div className="absolute inset-0 bg-slate-900/80 backdrop-blur-sm" />
          </div>
          <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-indigo-500/20 text-indigo-300 font-bold text-xs mb-8 border border-indigo-500/30 backdrop-blur-md">
              <span>{t('public.page.hero.badge')}</span>
            </div>
            <h1 className="text-4xl sm:text-6xl font-extrabold tracking-tight leading-tight max-w-4xl mx-auto">
              {page.title}
            </h1>
            <p className="mt-6 text-lg sm:text-xl text-indigo-100 max-w-2xl mx-auto font-normal leading-relaxed">
              {meta.description}
            </p>
            <div className="mt-10 flex flex-wrap justify-center gap-4">
              <button
                type="button"
                onClick={() => navigate('/blog')}
                className="bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold px-8 py-4 rounded-2xl shadow-xl shadow-indigo-600/30 flex items-center gap-2 transition-all cursor-pointer text-base group"
              >
                <span>{t('public.page.hero.exploreBlog')}</span>
                <ArrowRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
              </button>
              <button
                type="button"
                onClick={() => navigate('/about')}
                className="bg-slate-800/80 hover:bg-slate-800 text-slate-100 font-bold px-8 py-4 rounded-2xl border border-slate-700 backdrop-blur-md transition-all cursor-pointer text-base"
              >
                {t('public.page.hero.aboutUs')}
              </button>
            </div>
          </div>
        </div>
      ) : (
        <div className="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 pt-12 pb-16">
          <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="flex items-center gap-3 text-xs text-slate-500 font-semibold mb-3">
              <span className="flex items-center gap-1 text-indigo-600 dark:text-indigo-400">
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
            <h1 className="text-3xl sm:text-5xl font-extrabold tracking-tight text-slate-900 dark:text-white">
              {page.title}
            </h1>
            <p className="mt-4 text-base sm:text-lg text-slate-600 dark:text-slate-300 font-normal leading-relaxed max-w-3xl">
              {meta.description}
            </p>
            {meta.featuredImage && !isAbout && !isServices && (
              <div className="mt-8 rounded-3xl overflow-hidden shadow-xl max-h-[400px]">
                <img src={meta.featuredImage} alt={page.title} className="w-full h-full object-cover" />
              </div>
            )}
          </div>
        </div>
      )}

      <main className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
        <div className="bg-white dark:bg-slate-900 rounded-3xl p-8 sm:p-12 shadow-sm border border-slate-200/60 dark:border-slate-800/80">
          <MarkdownRenderer content={page.content} html={page.html} />
        </div>

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
          <div className="mt-12 bg-gradient-to-r from-indigo-900 to-violet-900 rounded-3xl p-8 sm:p-12 text-white shadow-xl text-center">
            <h3 className="text-2xl font-black">{t('public.page.services.ctaTitle')}</h3>
            <p className="mt-3 text-indigo-200 max-w-xl mx-auto text-sm">{t('public.page.services.ctaBody')}</p>
            <button
              type="button"
              onClick={() => navigate('/contact')}
              className="mt-6 bg-white hover:bg-indigo-50 text-indigo-950 font-extrabold px-8 py-3.5 rounded-xl shadow transition-all cursor-pointer text-sm"
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
