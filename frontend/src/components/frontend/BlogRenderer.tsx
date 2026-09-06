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
  Printer,
} from 'lucide-react';
import { resolveContentPreviewImage } from '../../utils/contentPreviewImage';
import { MEDIA_THUMB_WIDTH, resolvePublicMediaThumbnailUrl } from '../../api/media';
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
import { resolveArticlePrintEnabled } from '../../utils/contentPublicSettings';
import { BTN_PRIMARY, INPUT_THEME, PUBLIC_CARD, PUBLIC_SPINNER } from '../../theme/publicUiClasses';
import { blogSidebarApi, type BlogSidebarPayload } from '../../api/blogSidebar';
import { BlogSidebar } from './BlogSidebar';
import { resolveBlogSidebarSettings } from '../../utils/blogSidebarSettings';

export const BlogRenderer: React.FC = () => {
  const { t, locale } = useI18n();
  const sortOptions = useMemo(
    () =>
      [
        { value: 'newest' as const, label: t('public.blog.sort.newest') },
        { value: 'oldest' as const, label: t('public.blog.sort.oldest') },
        { value: 'title' as const, label: t('public.blog.sort.title') },
        { value: 'popular' as const, label: t('public.blog.sort.popular') },
      ] satisfies { value: BlogSort; label: string }[],
    [t]
  );
  const { slug } = useParams<{ slug?: string }>();
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();
  const { settings } = useSettingsContext();

  const itemsPerPage = resolveBlogItemsPerPage(settings.content);
  const showReadingTime = resolveShowReadingTime(settings.content);
  const articlePrintEnabled = resolveArticlePrintEnabled(settings.content);
  const sidebarSettings = useMemo(
    () => resolveBlogSidebarSettings(settings.content),
    [settings.content]
  );
  const selectedTag = searchParams.get('tag');
  const selectedCategory = searchParams.get('category');
  const sort = parseBlogSort(searchParams.get('sort'));
  const currentPage = Math.max(1, Number.parseInt(searchParams.get('page') ?? '1', 10) || 1);

  const [listArticles, setListArticles] = useState<Article[]>([]);
  const [listMeta, setListMeta] = useState<PaginationMeta | null>(null);
  const [listLoading, setListLoading] = useState(false);
  const [activeArticle, setActiveArticle] = useState<Article | null>(null);
  const [detailLoading, setDetailLoading] = useState(false);
  const [navArticles, setNavArticles] = useState<Article[]>([]);
  const [sidebarData, setSidebarData] = useState<BlogSidebarPayload | null>(null);

  useEffect(() => {
    if (!sidebarSettings.enabled) {
      setSidebarData(null);
      return;
    }

    let cancelled = false;
    const loadSidebar = async () => {
      const payload = await blogSidebarApi.fetch();
      if (!cancelled) {
        setSidebarData(payload);
      }
    };

    void loadSidebar();

    return () => {
      cancelled = true;
    };
  }, [sidebarSettings.enabled]);

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
        if (selectedCategory) {
          params.set('category', selectedCategory);
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
  }, [slug, currentPage, itemsPerPage, selectedTag, selectedCategory, sort]);

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
  const listPath = buildBlogListPath({ page: safePage, tag: selectedTag, category: selectedCategory, sort });

  const updateListParams = (patch: {
    page?: number;
    tag?: string | null;
    category?: string | null;
    sort?: BlogSort;
  }) => {
    const next = new URLSearchParams(searchParams);
    const nextPage = patch.page ?? safePage;
    const nextTag = patch.tag !== undefined ? patch.tag : selectedTag;
    const nextCategory = patch.category !== undefined ? patch.category : selectedCategory;
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

    if (nextCategory) {
      next.set('category', nextCategory);
    } else {
      next.delete('category');
    }

    if (nextSort === 'newest') {
      next.delete('sort');
    } else {
      next.set('sort', nextSort);
    }

    setSearchParams(next, { replace: true });
  };

  const sidebarActive = sidebarSettings.enabled && sidebarData?.enabled === true;
  const sidebarElement = sidebarActive ? (
    <BlogSidebar
      data={sidebarData}
      selectedTag={selectedTag}
      selectedCategory={selectedCategory}
      onSelectTag={(tag) => updateListParams({ page: 1, tag })}
      onSelectCategory={(category) => updateListParams({ page: 1, category })}
      onOpenArticle={(articleSlug) => navigate(`/blog/${articleSlug}`)}
      settings={{
        showTags: sidebarSettings.showTags,
        showCategories: sidebarSettings.showCategories,
        showLatest: sidebarSettings.showLatest,
        showPopular: sidebarSettings.showPopular,
      }}
    />
  ) : null;

  const wrapWithSidebar = (content: React.ReactNode, wide = false) => {
    const maxWidth = wide ? 'max-w-7xl' : 'max-w-4xl';

    if (!sidebarActive || !sidebarElement) {
      return (
        <div className={`${maxWidth} mx-auto px-4 sm:px-6 lg:px-8`}>
          {content}
        </div>
      );
    }

    return (
      <div className={`${maxWidth} mx-auto px-4 sm:px-6 lg:px-8`}>
        <div
          className={`pg-blog-with-sidebar ${
            sidebarSettings.placement === 'left' ? 'pg-blog-sidebar-left' : ''
          }`}
        >
          <div className="pg-blog-main">{content}</div>
          {sidebarElement}
        </div>
      </div>
    );
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
        <div className={PUBLIC_SPINNER} />
      </div>
    );
  }

  if (slug && !detailLoading && !activeArticle) {
    return (
      <div className="min-h-[50vh] flex flex-col items-center justify-center px-4 text-center">
        <h1 className="text-2xl font-bold text-theme-text">{t('public.errors.notFoundCode')}</h1>
        <p className="mt-2 text-theme-text-muted">{t('public.blog.errors.articleNotFound')}</p>
        <button
          type="button"
          onClick={() => navigate('/blog')}
          className={`mt-6 ${BTN_PRIMARY} px-6 py-2.5 text-sm`}
        >
          {t('public.blog.backToBlog')}
        </button>
      </div>
    );
  }

  if (activeArticle) {
    const defaultAuthorName = String(
      settings.content?.blogAuthorName || settings.general?.siteName || t('public.defaults.editorial')
    ).trim();
    const author = activeArticle.author || defaultAuthorName;
    const authorBio = String(activeArticle.authorBio ?? '').trim();
    const showAuthorBox = activeArticle.showAuthorBox !== false && settings.content?.blogShowAuthorBox !== false;
    const authorAvatarUrl = activeArticle.authorAvatarUrl
      ? resolvePublicMediaThumbnailUrl(activeArticle.authorAvatarUrl, MEDIA_THUMB_WIDTH.avatar)
      : '';
    const image = resolveContentPreviewImage(activeArticle, MEDIA_THUMB_WIDTH.hero);
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
      <div className="min-h-screen bg-theme-surface text-theme-text pb-24 transition-colors">
        {wrapWithSidebar(
          <>
            <div className="pt-10 pg-no-print">
              <button
                type="button"
                onClick={() => navigate(listPath)}
                className="inline-flex items-center gap-2 text-sm font-bold text-theme-text-muted hover:text-theme-primary transition-colors cursor-pointer mb-6"
              >
                <ArrowLeft className="w-4 h-4" />
                <span>{t('public.blog.backToList')}</span>
              </button>
            </div>

            <article className="pg-print-article">
            <header className="py-6">
          <div className="flex flex-wrap items-center gap-2 mb-4 pg-no-print">
            {activeArticle.tags?.map((tag) => (
              <span
                key={tag}
                className="text-xs bg-theme-primary/10 text-theme-primary font-extrabold px-3 py-1 rounded-lg flex items-center gap-1"
              >
                <Tag className="w-3 h-3" /> {tag}
              </span>
            ))}
          </div>
          <h1 className="text-3xl sm:text-5xl font-black tracking-tight text-theme-text leading-tight">
            {activeArticle.title}
          </h1>
          <div className="mt-6 flex items-center gap-6 text-xs text-theme-text-muted border-y border-theme-border/80 py-4">
            <div className="flex items-center gap-2">
              {authorAvatarUrl ? (
                <img
                  src={authorAvatarUrl}
                  alt={author}
                  className="h-8 w-8 rounded-full object-cover ring-1 ring-theme-border/80"
                />
              ) : (
                <div className="w-8 h-8 rounded-full bg-gradient-to-tr from-theme-primary to-theme-accent flex items-center justify-center text-theme-primary-foreground font-bold text-xs">
                  {author.charAt(0)}
                </div>
              )}
              <div>
                <div className="font-bold text-theme-text">{author}</div>
                <div>{t('public.blog.editorialAuthor')}</div>
              </div>
            </div>
            <div className="flex items-center gap-1.5 ml-auto flex-wrap justify-end">
              <Calendar className="w-4 h-4 text-theme-text-muted" />
              <span title={dates.primaryTitle}>{dates.primary}</span>
              {dates.secondary && (
                <>
                  <span className="text-theme-border">•</span>
                  <span className="rounded-full bg-amber-50 dark:bg-amber-950/40 px-2 py-0.5 text-amber-700 dark:text-amber-300 font-bold" title={dates.secondaryTitle}>
                    {dates.secondary}
                  </span>
                </>
              )}
              {showReadingTime && activeArticle.readingTime > 0 && (
                <>
                  <span className="text-theme-border">•</span>
                  <span className="inline-flex items-center gap-1 font-semibold">
                    <Clock className="w-4 h-4" />
                    {formatReadingTime(activeArticle.readingTime, locale)}
                  </span>
                </>
              )}
              {articlePrintEnabled && (
                <>
                  <span className="text-theme-border pg-no-print">•</span>
                  <button
                    type="button"
                    onClick={() => window.print()}
                    className="pg-no-print inline-flex items-center gap-1.5 font-semibold text-theme-primary hover:text-theme-accent transition-colors"
                    title={t('public.blog.printArticle')}
                  >
                    <Printer className="w-4 h-4" />
                    <span>{t('public.blog.printArticle')}</span>
                  </button>
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

            <main className="mt-10">
          <div className={`${PUBLIC_CARD} p-8 sm:p-12 pg-print-body`}>
            <MarkdownRenderer content={activeArticle.content} html={activeArticle.html} />
          </div>

          {showAuthorBox && authorBio && (
            <div className="mt-12 bg-theme-primary/10 border border-theme-primary/20 rounded-3xl p-6 sm:p-8 flex items-center gap-6">
              {authorAvatarUrl ? (
                <img
                  src={authorAvatarUrl}
                  alt={author}
                  className="w-16 h-16 rounded-2xl object-cover shrink-0 shadow-lg border border-theme-border/50"
                />
              ) : (
                <div className="w-16 h-16 rounded-2xl bg-gradient-to-br from-theme-primary to-theme-accent flex items-center justify-center text-theme-primary-foreground font-extrabold text-2xl shrink-0 shadow-lg">
                  {author.charAt(0)}
                </div>
              )}
              <div>
                <h4 className="font-bold text-lg text-theme-text">
                  {t('public.blog.aboutAuthor', { author })}
                </h4>
                <p className="text-xs sm:text-sm text-theme-text-muted mt-1">{authorBio}</p>
              </div>
            </div>
          )}
            </main>
            </article>

          {(prevArticle || nextArticle) && (
            <nav
              className="mt-10 grid grid-cols-1 sm:grid-cols-2 gap-4 pg-no-print"
              aria-label={t('public.blog.articleNav.ariaLabel')}
            >
              {prevArticle ? (
                <button
                  type="button"
                  onClick={() => navigate(`/blog/${prevArticle.slug}`)}
                  className="text-left rounded-2xl border border-theme-border bg-theme-surface-elevated p-5 hover:border-theme-primary/50 transition-colors group"
                >
                  <span className="text-xs font-bold uppercase tracking-wide text-theme-text-muted flex items-center gap-1">
                    <ChevronLeft className="w-4 h-4" /> {t('public.blog.articleNav.previous')}
                  </span>
                  <span className="mt-2 block text-sm font-bold text-theme-text group-hover:text-theme-primary line-clamp-2">
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
                  className="text-right rounded-2xl border border-theme-border bg-theme-surface-elevated p-5 hover:border-theme-primary/50 transition-colors group sm:col-start-2"
                >
                  <span className="text-xs font-bold uppercase tracking-wide text-theme-text-muted flex items-center justify-end gap-1">
                    {t('public.blog.articleNav.next')} <ChevronRight className="w-4 h-4" />
                  </span>
                  <span className="mt-2 block text-sm font-bold text-theme-text group-hover:text-theme-primary line-clamp-2">
                    {nextArticle.title}
                  </span>
                </button>
              ) : null}
            </nav>
          )}

          <div className="pg-no-print">
          <ArticleComments
            articleSlug={activeArticle.slug}
            enabled={commentsEnabled}
            allowGuests={allowGuests}
            requireApproval={requireApproval}
          />
          </div>
          </>,
          true
        )}
      </div>
    );
  }

  const rangeStart = filteredTotal === 0 ? 0 : (safePage - 1) * itemsPerPage + 1;
  const rangeEnd = Math.min(safePage * itemsPerPage, filteredTotal);

  if (listLoading && listArticles.length === 0) {
    return (
      <div className="min-h-[50vh] flex items-center justify-center">
        <div className={PUBLIC_SPINNER} />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-theme-surface text-theme-text pb-28 transition-colors">
      <div className="bg-theme-surface-elevated border-b border-theme-border pt-16 pb-20">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <div className="w-12 h-12 rounded-2xl bg-theme-primary/10 flex items-center justify-center text-theme-primary mx-auto mb-4">
            <BookOpen className="w-6 h-6" />
          </div>
          <h1 className="text-4xl sm:text-6xl font-black tracking-tight text-theme-text">
            {t('public.blog.list.title')}
          </h1>
          <p className="mt-4 text-base sm:text-lg text-theme-text-muted max-w-2xl mx-auto">
            {t('public.blog.list.subtitle')}
          </p>
          <div className="mt-8 flex flex-wrap justify-center gap-2">
            <button
              type="button"
              onClick={() => updateListParams({ page: 1, tag: null, category: null })}
              className={`px-4 py-2 rounded-xl text-xs font-extrabold transition-all cursor-pointer ${
                selectedTag === null
                  ? `${BTN_PRIMARY} shadow-md`
                  : 'bg-theme-surface hover:bg-theme-surface-elevated text-theme-text-muted'
              }`}
            >
              {t('public.blog.list.allArticles', { count: totalPublished })}
            </button>
            {!sidebarActive || !sidebarSettings.showTags
              ? allTags.map((tag) => (
                  <button
                    key={tag}
                    type="button"
                    onClick={() => updateListParams({ page: 1, tag: selectedTag === tag ? null : tag })}
                    className={`px-4 py-2 rounded-xl text-xs font-extrabold transition-all cursor-pointer flex items-center gap-1.5 ${
                      selectedTag === tag
                        ? `${BTN_PRIMARY} shadow-md`
                        : 'bg-theme-surface hover:bg-theme-surface-elevated text-theme-text-muted'
                    }`}
                  >
                    <Tag className="w-3 h-3" />
                    <span>{tag}</span>
                  </button>
                ))
              : null}
          </div>
          <div className="mt-6 flex flex-wrap items-center justify-center gap-3">
            <label htmlFor="blog-sort" className="text-xs font-bold text-theme-text-muted uppercase tracking-wide">
              {t('public.blog.list.sortLabel')}
            </label>
            <select
              id="blog-sort"
              value={sort}
              onChange={(event) => updateListParams({ page: 1, sort: parseBlogSort(event.target.value) })}
              className={`form-select text-sm rounded-xl ${INPUT_THEME}`}
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
        {wrapWithSidebar(
          <>
        {filteredTotal > 0 && (
          <p className="text-center text-xs font-semibold text-theme-text-muted mb-8">
            {t('public.blog.list.range', { start: rangeStart, end: rangeEnd, total: filteredTotal })}
            {totalPages > 1 ? t('public.blog.list.pageOf', { page: safePage, totalPages }) : ''}
          </p>
        )}

        <div
          className={`grid grid-cols-1 gap-8 ${
            sidebarActive ? 'md:grid-cols-2' : 'md:grid-cols-2 lg:grid-cols-3'
          }`}
        >
          {paginatedArticles.map((article) => {
            const author = article.author || String(
              settings.content?.blogAuthorName || settings.general?.siteName || article.frontMatter?.author || t('public.defaults.editorial')
            );
            const image = resolveContentPreviewImage(article, MEDIA_THUMB_WIDTH.card);
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
                className={`text-left ${PUBLIC_CARD} overflow-hidden shadow-md hover:shadow-2xl transition-all hover:-translate-y-1.5 flex flex-col group cursor-pointer`}
              >
                <div className="h-56 overflow-hidden relative bg-theme-surface">
                  {image && (
                    <img
                      src={image}
                      alt={article.title}
                      loading="lazy"
                      decoding="async"
                      className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                    />
                  )}
                  <div className="absolute top-4 left-4 flex flex-wrap gap-1">
                    {article.tags?.slice(0, 2).map((tag) => (
                      <span
                        key={tag}
                        className="bg-theme-text/90 backdrop-blur-md text-theme-primary-foreground text-[11px] font-bold px-2.5 py-1 rounded-lg"
                      >
                        {tag}
                      </span>
                    ))}
                  </div>
                </div>
                <div className="p-8 flex-1 flex flex-col justify-between">
                  <div>
                    <div className="flex flex-wrap items-center gap-2 text-xs text-theme-text-muted mb-3 font-medium">
                      <span className="inline-flex items-center gap-1 rounded-full bg-theme-surface px-2.5 py-1" title={dates.primaryTitle}>
                        <Calendar className="w-3.5 h-3.5 text-theme-primary" />
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
                        <span className="inline-flex items-center gap-1 rounded-full bg-theme-primary/10 px-2.5 py-1 text-theme-primary font-bold">
                          <Clock className="w-3.5 h-3.5" />
                          {formatReadingTime(article.readingTime, locale)}
                        </span>
                      )}
                    </div>
                    <h3 className="text-xl font-extrabold text-theme-text group-hover:text-theme-primary transition-colors leading-snug tracking-tight">
                      {article.title}
                    </h3>
                    <p className="mt-3 text-theme-text-muted text-sm leading-relaxed line-clamp-3">
                      {desc}
                    </p>
                  </div>
                  <div className="mt-8 pt-4 border-t border-theme-border/80 flex items-center justify-between text-xs font-bold text-theme-primary">
                    <span>{t('public.blog.list.readMore')}</span>
                    <span className="group-hover:translate-x-1 transition-transform">→</span>
                  </div>
                </div>
              </button>
            );
          })}
        </div>

        {filteredTotal === 0 && !listLoading && (
          <div className={`${PUBLIC_CARD} p-16 text-center`}>
            <h3 className="text-2xl font-bold">{t('public.blog.list.emptyTitle')}</h3>
            <button
              type="button"
              onClick={() => updateListParams({ page: 1, tag: null, category: null })}
              className={`mt-6 ${BTN_PRIMARY} px-6 py-2.5 text-sm`}
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
              className="p-2.5 rounded-xl text-theme-text-muted hover:text-theme-primary hover:bg-theme-surface disabled:opacity-30 disabled:cursor-not-allowed transition-all"
              aria-label={t('public.blog.pagination.first')}
            >
              <ChevronsLeft className="w-5 h-5" />
            </button>
            <button
              type="button"
              onClick={() => updateListParams({ page: safePage - 1 })}
              disabled={!hasPrev}
              className="p-2.5 rounded-xl text-theme-text-muted hover:text-theme-primary hover:bg-theme-surface disabled:opacity-30 disabled:cursor-not-allowed transition-all"
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
                    ? `${BTN_PRIMARY} shadow-md`
                    : 'text-theme-text-muted hover:bg-theme-surface'
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
              className="p-2.5 rounded-xl text-theme-text-muted hover:text-theme-primary hover:bg-theme-surface disabled:opacity-30 disabled:cursor-not-allowed transition-all"
              aria-label={t('public.blog.pagination.next')}
            >
              <ChevronRight className="w-5 h-5" />
            </button>
            <button
              type="button"
              onClick={() => updateListParams({ page: totalPages })}
              disabled={!hasNext}
              className="p-2.5 rounded-xl text-theme-text-muted hover:text-theme-primary hover:bg-theme-surface disabled:opacity-30 disabled:cursor-not-allowed transition-all"
              aria-label={t('public.blog.pagination.last')}
            >
              <ChevronsRight className="w-5 h-5" />
            </button>
          </div>
        )}
          </>,
          true
        )}
      </main>
    </div>
  );
};

export default BlogRenderer;
