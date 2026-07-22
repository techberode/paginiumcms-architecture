// frontend/src/components/frontend/BlogRenderer.tsx
import React, { useEffect, useMemo, useState } from 'react';
import { useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { useSettingsContext } from '../../context/SettingsContext';
import { useI18n } from '../../context/I18nContext';
import apiClient, { type PaginationMeta } from '../../api/client';
import { Article } from '../../api/types';
import { MarkdownRenderer } from '../common/MarkdownRenderer';
import { ArticleComments } from './ArticleComments';
import {
  Calendar,
  Clock,
  User,
  Tag,
  BookOpen,
  ArrowLeft,
  ChevronLeft,
  ChevronRight,
  ChevronsLeft,
  ChevronsRight,
} from 'lucide-react';
import { resolveContentPreviewImage } from '../../utils/contentPreviewImage';
import {
  buildBlogListPath,
  blogSortToApiSort,
  getAdjacentArticles,
  parseBlogSort,
  resolveBlogItemsPerPage,
  type BlogSort,
} from '../../utils/blogArticles';
import { formatContentDateLabels } from '../../utils/contentDates';
import { formatReadingTime, resolveShowReadingTime } from '../../utils/readingTime';

export const BlogRenderer: React.FC = () => {
  const { t, locale } = useI18n();
  const sortOptions = useMemo(
    () =>
      [
        { value: 'newest' as const, label: t('public.blog.sort.newest') },
        { value: 'oldest' as const, label: t('public.blog.sort.oldest') },
        { value: 'title' as const, label: t('public.blog.sort.title') },
      ] satisfies { value: BlogSort; label: string }[],
    [t]
  );
  const { slug } = useParams<{ slug?: string }>();
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();
  const { settings } = useSettingsContext();

  const itemsPerPage = resolveBlogItemsPerPage(settings.content);
  const showReadingTime = resolveShowReadingTime(settings.content);
  const selectedTag = searchParams.get('tag');
  const sort = parseBlogSort(searchParams.get('sort'));
  const currentPage = Math.max(1, Number.parseInt(searchParams.get('page') ?? '1', 10) || 1);

  const [listArticles, setListArticles] = useState<Article[]>([]);
  const [listMeta, setListMeta] = useState<PaginationMeta | null>(null);
  const [listLoading, setListLoading] = useState(false);
  const [activeArticle, setActiveArticle] = useState<Article | null>(null);
  const [detailLoading, setDetailLoading] = useState(false);
  const [navArticles, setNavArticles] = useState<Article[]>([]);

  useEffect(() => {
    if (slug) {
      return;
    }

    let cancelled = false;
    const loadList = async () => {
      setListLoading(true);
      try {
        const params = new URLSearchParams({
          page: String(currentPage),
          per_page: String(itemsPerPage),
          sort: blogSortToApiSort(sort),
        });
        if (selectedTag) {
          params.set('tag', selectedTag);
        }

        const response = await apiClient.get<Article[]>(`/api/articles?${params.toString()}`);
        if (cancelled) {
          return;
        }
        if (response.success) {
          setListArticles(response.data ?? []);
          setListMeta(response.meta ?? null);
        } else {
          setListArticles([]);
          setListMeta(null);
        }
      } finally {
        if (!cancelled) {
          setListLoading(false);
        }
      }
    };

    void loadList();

    return () => {
      cancelled = true;
    };
  }, [slug, currentPage, itemsPerPage, selectedTag, sort]);

  useEffect(() => {
    if (!slug) {
      setActiveArticle(null);
      return;
    }

    let cancelled = false;
    const loadDetail = async () => {
      setDetailLoading(true);
      try {
        const response = await apiClient.get<Article>(`/api/articles/${encodeURIComponent(slug)}`);
        if (cancelled) {
          return;
        }
        setActiveArticle(response.success ? (response.data ?? null) : null);
      } finally {
        if (!cancelled) {
          setDetailLoading(false);
        }
      }
    };

    void loadDetail();

    return () => {
      cancelled = true;
    };
  }, [slug]);

  useEffect(() => {
    if (!slug) {
      setNavArticles([]);
      return;
    }

    let cancelled = false;
    const loadNav = async () => {
      const params = new URLSearchParams({
        page: '1',
        per_page: '100',
        sort: blogSortToApiSort(sort),
      });
      const response = await apiClient.get<Article[]>(`/api/articles?${params.toString()}`);
      if (!cancelled && response.success) {
        setNavArticles(response.data ?? []);
      }
    };

    void loadNav();

    return () => {
      cancelled = true;
    };
  }, [slug, sort]);

  const allTags = listMeta?.tags ?? [];
  const totalPublished = listMeta?.total_published ?? listMeta?.total ?? 0;
  const filteredTotal = listMeta?.total ?? 0;
  const totalPages = listMeta?.total_pages ?? 1;
  const safePage = Math.min(currentPage, Math.max(1, totalPages));
  const paginatedArticles = listArticles;

  const hasPrev = safePage > 1;
  const hasNext = safePage < totalPages;
  const listPath = buildBlogListPath({ page: safePage, tag: selectedTag, sort });

  const updateListParams = (patch: { page?: number; tag?: string | null; sort?: BlogSort }) => {
    const next = new URLSearchParams(searchParams);
    const nextPage = patch.page ?? safePage;
    const nextTag = patch.tag !== undefined ? patch.tag : selectedTag;
    const nextSort = patch.sort ?? sort;

    if (nextPage <= 1) {
      next.delete('page');
    } else {
      next.set('page', String(nextPage));
    }

    if (nextTag) {
      next.set('tag', nextTag);
    } else {
      next.delete('tag');
    }

    if (nextSort === 'newest') {
      next.delete('sort');
    } else {
      next.set('sort', nextSort);
    }

    setSearchParams(next, { replace: true });
  };

  const { prev: prevArticle, next: nextArticle } = useMemo(() => {
    if (!activeArticle) {
      return { prev: null, next: null };
    }
    return getAdjacentArticles(navArticles, activeArticle.slug);
  }, [activeArticle, navArticles]);

  useEffect(() => {
    if (slug || currentPage <= totalPages) {
      return;
    }
    const next = new URLSearchParams(searchParams);
    if (totalPages <= 1) {
      next.delete('page');
    } else {
      next.set('page', String(totalPages));
    }
    setSearchParams(next, { replace: true });
  }, [slug, currentPage, totalPages, searchParams, setSearchParams]);

  if (slug && detailLoading) {
    return (
      <div className="min-h-[50vh] flex items-center justify-center">
        <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600" />
      </div>
    );
  }

  if (slug && !detailLoading && !activeArticle) {
    return (
      <div className="min-h-[50vh] flex flex-col items-center justify-center px-4 text-center">
        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">{t('public.errors.notFoundCode')}</h1>
        <p className="mt-2 text-slate-500">{t('public.blog.errors.articleNotFound')}</p>
        <button
          type="button"
          onClick={() => navigate('/blog')}
          className="mt-6 bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-bold"
        >
          {t('public.blog.backToBlog')}
        </button>
      </div>
    );
  }

  if (activeArticle) {
    const author = activeArticle.author || String(activeArticle.frontMatter?.author ?? t('public.defaults.editorial'));
    const image = resolveContentPreviewImage(activeArticle);
    const authorBio =
      activeArticle.excerpt || String(activeArticle.frontMatter?.description ?? '');
    const dates = formatContentDateLabels(
      {
        createdAt: activeArticle.createdAt,
        updatedAt: activeArticle.updatedAt,
        frontMatterDate: activeArticle.frontMatter?.date as string | number | undefined,
      },
      locale
    );

    const globalCommentsEnabled = settings.comments?.enabled !== false;
    const articleCommentsEnabled = activeArticle.commentsEnabled !== false;
    const commentsEnabled = globalCommentsEnabled && articleCommentsEnabled;

    const globalRequireApproval = settings.comments?.requireApproval !== false;
    const globalAllowGuests = settings.comments?.allowGuestComments !== false;
    const requireApproval =
      activeArticle.commentsRequireApproval ?? globalRequireApproval;
    const allowGuests =
      activeArticle.commentsAllowGuests ?? globalAllowGuests;

    return (
      <div className="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 pb-24 transition-colors">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-10">
          <button
            type="button"
            onClick={() => navigate(listPath)}
            className="inline-flex items-center gap-2 text-sm font-bold text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors cursor-pointer mb-6"
          >
            <ArrowLeft className="w-4 h-4" />
            <span>{t('public.blog.backToList')}</span>
          </button>
        </div>

        <header className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <div className="flex flex-wrap items-center gap-2 mb-4">
            {activeArticle.tags?.map((tag) => (
              <span
                key={tag}
                className="text-xs bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-extrabold px-3 py-1 rounded-lg flex items-center gap-1"
              >
                <Tag className="w-3 h-3" /> {tag}
              </span>
            ))}
          </div>
          <h1 className="text-3xl sm:text-5xl font-black tracking-tight text-slate-900 dark:text-white leading-tight">
            {activeArticle.title}
          </h1>
          <div className="mt-6 flex items-center gap-6 text-xs text-slate-500 dark:text-slate-400 border-y border-slate-200/80 dark:border-slate-800 py-4">
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-500 to-rose-500 flex items-center justify-center text-white font-bold text-xs">
                {author.charAt(0)}
              </div>
              <div>
                <div className="font-bold text-slate-800 dark:text-slate-200">{author}</div>
                <div>{t('public.blog.editorialAuthor')}</div>
              </div>
            </div>
            <div className="flex items-center gap-1.5 ml-auto flex-wrap justify-end">
              <Calendar className="w-4 h-4 text-slate-400" />
              <span title={dates.primaryTitle}>{dates.primary}</span>
              {dates.secondary && (
                <>
                  <span className="text-slate-300">•</span>
                  <span className="rounded-full bg-amber-50 dark:bg-amber-950/40 px-2 py-0.5 text-amber-700 dark:text-amber-300 font-bold" title={dates.secondaryTitle}>
                    {dates.secondary}
                  </span>
                </>
              )}
              {showReadingTime && activeArticle.readingTime > 0 && (
                <>
                  <span className="text-slate-300">•</span>
                  <span className="inline-flex items-center gap-1 font-semibold">
                    <Clock className="w-4 h-4" />
                    {formatReadingTime(activeArticle.readingTime, locale)}
                  </span>
                </>
              )}
            </div>
          </div>
          {image && (
            <div className="mt-8 rounded-3xl overflow-hidden shadow-2xl max-h-[480px]">
              <img src={image} alt={activeArticle.title} className="w-full h-full object-cover" />
            </div>
          )}
        </header>

        <main className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
          <div className="bg-white dark:bg-slate-900 rounded-3xl p-8 sm:p-12 shadow-sm border border-slate-200/60 dark:border-slate-800/80">
            <MarkdownRenderer content={activeArticle.content} html={activeArticle.html} />
          </div>

          {(prevArticle || nextArticle) && (
            <nav
              className="mt-10 grid grid-cols-1 sm:grid-cols-2 gap-4"
              aria-label={t('public.blog.articleNav.ariaLabel')}
            >
              {prevArticle ? (
                <button
                  type="button"
                  onClick={() => navigate(`/blog/${prevArticle.slug}`)}
                  className="text-left rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 hover:border-indigo-300 dark:hover:border-indigo-700 transition-colors group"
                >
                  <span className="text-xs font-bold uppercase tracking-wide text-slate-400 flex items-center gap-1">
                    <ChevronLeft className="w-4 h-4" /> {t('public.blog.articleNav.previous')}
                  </span>
                  <span className="mt-2 block text-sm font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 line-clamp-2">
                    {prevArticle.title}
                  </span>
                </button>
              ) : (
                <div />
              )}
              {nextArticle ? (
                <button
                  type="button"
                  onClick={() => navigate(`/blog/${nextArticle.slug}`)}
                  className="text-right rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 hover:border-indigo-300 dark:hover:border-indigo-700 transition-colors group sm:col-start-2"
                >
                  <span className="text-xs font-bold uppercase tracking-wide text-slate-400 flex items-center justify-end gap-1">
                    {t('public.blog.articleNav.next')} <ChevronRight className="w-4 h-4" />
                  </span>
                  <span className="mt-2 block text-sm font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 line-clamp-2">
                    {nextArticle.title}
                  </span>
                </button>
              ) : null}
            </nav>
          )}

          {authorBio && (
            <div className="mt-12 bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/60 rounded-3xl p-6 sm:p-8 flex items-center gap-6">
              <div className="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-extrabold text-2xl shrink-0 shadow-lg shadow-indigo-500/25">
                {author.charAt(0)}
              </div>
              <div>
                <h4 className="font-bold text-lg text-slate-900 dark:text-white">
                  {t('public.blog.aboutAuthor', { author })}
                </h4>
                <p className="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">{authorBio}</p>
              </div>
            </div>
          )}

          <ArticleComments
            articleSlug={activeArticle.slug}
            enabled={commentsEnabled}
            allowGuests={allowGuests}
            requireApproval={requireApproval}
          />
        </main>
      </div>
    );
  }

  const rangeStart = filteredTotal === 0 ? 0 : (safePage - 1) * itemsPerPage + 1;
  const rangeEnd = Math.min(safePage * itemsPerPage, filteredTotal);

  if (listLoading && listArticles.length === 0) {
    return (
      <div className="min-h-[50vh] flex items-center justify-center">
        <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600" />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 pb-28 transition-colors">
      <div className="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 pt-16 pb-20">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <div className="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400 mx-auto mb-4">
            <BookOpen className="w-6 h-6" />
          </div>
          <h1 className="text-4xl sm:text-6xl font-black tracking-tight text-slate-900 dark:text-white">
            {t('public.blog.list.title')}
          </h1>
          <p className="mt-4 text-base sm:text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
            {t('public.blog.list.subtitle')}
          </p>
          <div className="mt-8 flex flex-wrap justify-center gap-2">
            <button
              type="button"
              onClick={() => updateListParams({ page: 1, tag: null })}
              className={`px-4 py-2 rounded-xl text-xs font-extrabold transition-all cursor-pointer ${
                selectedTag === null
                  ? 'bg-indigo-600 text-white shadow-md'
                  : 'bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300'
              }`}
            >
              {t('public.blog.list.allArticles', { count: totalPublished })}
            </button>
            {allTags.map((tag) => (
              <button
                key={tag}
                type="button"
                onClick={() => updateListParams({ page: 1, tag: selectedTag === tag ? null : tag })}
                className={`px-4 py-2 rounded-xl text-xs font-extrabold transition-all cursor-pointer flex items-center gap-1.5 ${
                  selectedTag === tag
                    ? 'bg-indigo-600 text-white shadow-md'
                    : 'bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300'
                }`}
              >
                <Tag className="w-3 h-3" />
                <span>{tag}</span>
              </button>
            ))}
          </div>
          <div className="mt-6 flex flex-wrap items-center justify-center gap-3">
            <label htmlFor="blog-sort" className="text-xs font-bold text-slate-500 uppercase tracking-wide">
              {t('public.blog.list.sortLabel')}
            </label>
            <select
              id="blog-sort"
              value={sort}
              onChange={(event) => updateListParams({ page: 1, sort: parseBlogSort(event.target.value) })}
              className="form-select text-sm rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900"
            >
              {sortOptions.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-16">
        {filteredTotal > 0 && (
          <p className="text-center text-xs font-semibold text-slate-500 dark:text-slate-400 mb-8">
            {t('public.blog.list.range', { start: rangeStart, end: rangeEnd, total: filteredTotal })}
            {totalPages > 1 ? t('public.blog.list.pageOf', { page: safePage, totalPages }) : ''}
          </p>
        )}

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          {paginatedArticles.map((article) => {
            const author = article.author || String(article.frontMatter?.author ?? t('public.defaults.editorial'));
            const image = resolveContentPreviewImage(article);
            const desc = article.excerpt || String(article.frontMatter?.description ?? '');
            const dates = formatContentDateLabels(
              {
                createdAt: article.createdAt,
                updatedAt: article.updatedAt,
                frontMatterDate: article.frontMatter?.date as string | number | undefined,
              },
              locale
            );

            return (
              <button
                key={article.id}
                type="button"
                onClick={() => navigate(`/blog/${article.slug}`)}
                className="text-left bg-white dark:bg-slate-900 rounded-3xl overflow-hidden border border-slate-200/80 dark:border-slate-800 shadow-md hover:shadow-2xl transition-all hover:-translate-y-1.5 flex flex-col group cursor-pointer"
              >
                <div className="h-56 overflow-hidden relative bg-slate-100 dark:bg-slate-800">
                  {image && (
                    <img
                      src={image}
                      alt={article.title}
                      className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                    />
                  )}
                  <div className="absolute top-4 left-4 flex flex-wrap gap-1">
                    {article.tags?.slice(0, 2).map((tag) => (
                      <span
                        key={tag}
                        className="bg-slate-900/90 backdrop-blur-md text-white text-[11px] font-bold px-2.5 py-1 rounded-lg"
                      >
                        {tag}
                      </span>
                    ))}
                  </div>
                </div>
                <div className="p-8 flex-1 flex flex-col justify-between">
                  <div>
                    <div className="flex flex-wrap items-center gap-2 text-xs text-slate-400 mb-3 font-medium">
                      <span className="inline-flex items-center gap-1 rounded-full bg-slate-50 dark:bg-slate-800/80 px-2.5 py-1" title={dates.primaryTitle}>
                        <Calendar className="w-3.5 h-3.5 text-indigo-500" />
                        {dates.primary}
                      </span>
                      {dates.secondary && (
                        <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 dark:bg-amber-950/40 px-2.5 py-1 text-amber-700 dark:text-amber-300 font-bold" title={dates.secondaryTitle}>
                          {t('public.blog.list.updated', { date: dates.secondary })}
                        </span>
                      )}
                      <span className="inline-flex items-center gap-1">
                        <User className="w-3.5 h-3.5" />
                        {author}
                      </span>
                      {showReadingTime && article.readingTime > 0 && (
                        <span className="inline-flex items-center gap-1 rounded-full bg-indigo-50 dark:bg-indigo-950/40 px-2.5 py-1 text-indigo-700 dark:text-indigo-300 font-bold">
                          <Clock className="w-3.5 h-3.5" />
                          {formatReadingTime(article.readingTime, locale)}
                        </span>
                      )}
                    </div>
                    <h3 className="text-xl font-extrabold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors leading-snug tracking-tight">
                      {article.title}
                    </h3>
                    <p className="mt-3 text-slate-600 dark:text-slate-400 text-sm leading-relaxed line-clamp-3">
                      {desc}
                    </p>
                  </div>
                  <div className="mt-8 pt-4 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs font-bold text-indigo-600 dark:text-indigo-400">
                    <span>{t('public.blog.list.readMore')}</span>
                    <span className="group-hover:translate-x-1 transition-transform">→</span>
                  </div>
                </div>
              </button>
            );
          })}
        </div>

        {filteredTotal === 0 && !listLoading && (
          <div className="bg-white dark:bg-slate-900 rounded-3xl p-16 text-center border border-slate-200 dark:border-slate-800">
            <h3 className="text-2xl font-bold">{t('public.blog.list.emptyTitle')}</h3>
            <button
              type="button"
              onClick={() => updateListParams({ page: 1, tag: null })}
              className="mt-6 bg-indigo-600 text-white font-bold px-6 py-2.5 rounded-xl text-sm"
            >
              {t('public.blog.list.showAll')}
            </button>
          </div>
        )}

        {totalPages > 1 && (
          <div className="flex items-center justify-center gap-3 mt-16">
            <button
              type="button"
              onClick={() => updateListParams({ page: 1 })}
              disabled={!hasPrev}
              className="p-2.5 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
              aria-label={t('public.blog.pagination.first')}
            >
              <ChevronsLeft className="w-5 h-5" />
            </button>
            <button
              type="button"
              onClick={() => updateListParams({ page: safePage - 1 })}
              disabled={!hasPrev}
              className="p-2.5 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
              aria-label={t('public.blog.pagination.previous')}
            >
              <ChevronLeft className="w-5 h-5" />
            </button>
            {Array.from({ length: totalPages }, (_, index) => index + 1).map((page) => (
              <button
                key={page}
                type="button"
                onClick={() => updateListParams({ page })}
                className={`w-10 h-10 rounded-xl text-xs font-bold transition-all ${
                  page === safePage
                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/25'
                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
                }`}
                aria-current={page === safePage ? 'page' : undefined}
              >
                {page}
              </button>
            ))}
            <button
              type="button"
              onClick={() => updateListParams({ page: safePage + 1 })}
              disabled={!hasNext}
              className="p-2.5 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
              aria-label={t('public.blog.pagination.next')}
            >
              <ChevronRight className="w-5 h-5" />
            </button>
            <button
              type="button"
              onClick={() => updateListParams({ page: totalPages })}
              disabled={!hasNext}
              className="p-2.5 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
              aria-label={t('public.blog.pagination.last')}
            >
              <ChevronsRight className="w-5 h-5" />
            </button>
          </div>
        )}
      </main>
    </div>
  );
};

export default BlogRenderer;
