// frontend/src/components/frontend/BlogRenderer.tsx
import React, { useEffect, useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { usePublicSite } from '../../context/PublicSiteContext';
import { useSettingsContext } from '../../context/SettingsContext';
import { MarkdownRenderer } from '../common/MarkdownRenderer';
import { ArticleComments } from './ArticleComments';
import {
  Calendar,
  User,
  Tag,
  BookOpen,
  ArrowLeft,
  ChevronLeft,
  ChevronRight,
  ChevronsLeft,
  ChevronsRight,
} from 'lucide-react';

export const BlogRenderer: React.FC = () => {
  const { slug } = useParams<{ slug?: string }>();
  const navigate = useNavigate();
  const { articles } = usePublicSite();
  const { settings } = useSettingsContext();
  const [selectedTag, setSelectedTag] = useState<string | null>(null);
  const [currentPage, setCurrentPage] = useState(1);

  const itemsPerPage = Number(settings.content?.itemsPerPage ?? 6);

  const publishedArticles = useMemo(
    () =>
      [...articles].sort(
        (a, b) =>
          new Date(String(b.frontMatter?.date ?? b.createdAt)).getTime() -
          new Date(String(a.frontMatter?.date ?? a.createdAt)).getTime()
      ),
    [articles]
  );

  const allTags = useMemo(() => {
    const tagsSet = new Set<string>();
    publishedArticles.forEach((art) => art.tags?.forEach((t) => tagsSet.add(t)));
    return Array.from(tagsSet);
  }, [publishedArticles]);

  const activeArticle = useMemo(() => {
    if (!slug) {
      return null;
    }
    return publishedArticles.find((art) => art.slug === slug) ?? null;
  }, [slug, publishedArticles]);

  const filteredArticles = selectedTag
    ? publishedArticles.filter((art) => art.tags?.includes(selectedTag))
    : publishedArticles;

  const paginatedArticles = useMemo(() => {
    const start = (currentPage - 1) * itemsPerPage;
    return filteredArticles.slice(start, start + itemsPerPage);
  }, [filteredArticles, currentPage, itemsPerPage]);

  const totalPages = Math.max(1, Math.ceil(filteredArticles.length / itemsPerPage));
  const hasPrev = currentPage > 1;
  const hasNext = currentPage < totalPages;

  useEffect(() => {
    setCurrentPage(1);
  }, [selectedTag]);

  if (activeArticle) {
    const date = String(activeArticle.frontMatter?.date ?? activeArticle.createdAt);
    const author = activeArticle.author || String(activeArticle.frontMatter?.author ?? 'Redakcia');
    const image = activeArticle.featuredImage || String(activeArticle.frontMatter?.featuredImage ?? '');
    const authorBio =
      activeArticle.excerpt || String(activeArticle.frontMatter?.description ?? '');

    return (
      <div className="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 pb-24 transition-colors">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-10">
          <button
            type="button"
            onClick={() => navigate('/blog')}
            className="inline-flex items-center gap-2 text-sm font-bold text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors cursor-pointer mb-6"
          >
            <ArrowLeft className="w-4 h-4" />
            <span>Späť na prehľad článkov</span>
          </button>
        </div>

        <header className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <div className="flex flex-wrap items-center gap-2 mb-4">
            {activeArticle.tags?.map((t) => (
              <span
                key={t}
                className="text-xs bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-extrabold px-3 py-1 rounded-lg flex items-center gap-1"
              >
                <Tag className="w-3 h-3" /> {t}
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
                <div>Autor redakcie Paginium</div>
              </div>
            </div>
            <div className="flex items-center gap-1.5 ml-auto">
              <Calendar className="w-4 h-4 text-slate-400" />
              <span>{new Date(date).toLocaleDateString('sk-SK')}</span>
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

          {authorBio && (
            <div className="mt-12 bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/60 rounded-3xl p-6 sm:p-8 flex items-center gap-6">
              <div className="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-extrabold text-2xl shrink-0 shadow-lg shadow-indigo-500/25">
                {author.charAt(0)}
              </div>
              <div>
                <h4 className="font-bold text-lg text-slate-900 dark:text-white">O autorovi: {author}</h4>
                <p className="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">{authorBio}</p>
              </div>
            </div>
          )}

          <ArticleComments articleSlug={activeArticle.slug} />
        </main>
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
            Magazín &amp; Novinky
          </h1>
          <p className="mt-4 text-base sm:text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
            Články z FlatFile Markdown repozitára.
          </p>
          <div className="mt-8 flex flex-wrap justify-center gap-2">
            <button
              type="button"
              onClick={() => setSelectedTag(null)}
              className={`px-4 py-2 rounded-xl text-xs font-extrabold transition-all cursor-pointer ${
                selectedTag === null
                  ? 'bg-indigo-600 text-white shadow-md'
                  : 'bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300'
              }`}
            >
              Všetky články ({publishedArticles.length})
            </button>
            {allTags.map((t) => (
              <button
                key={t}
                type="button"
                onClick={() => setSelectedTag(selectedTag === t ? null : t)}
                className={`px-4 py-2 rounded-xl text-xs font-extrabold transition-all cursor-pointer flex items-center gap-1.5 ${
                  selectedTag === t
                    ? 'bg-indigo-600 text-white shadow-md'
                    : 'bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300'
                }`}
              >
                <Tag className="w-3 h-3" />
                <span>{t}</span>
              </button>
            ))}
          </div>
        </div>
      </div>

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-16">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          {paginatedArticles.map((article) => {
            const date = String(article.frontMatter?.date ?? article.createdAt);
            const author = article.author || String(article.frontMatter?.author ?? 'Redakcia');
            const image = article.featuredImage || String(article.frontMatter?.featuredImage ?? '');
            const desc = article.excerpt || String(article.frontMatter?.description ?? '');

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
                    {article.tags?.slice(0, 2).map((t) => (
                      <span
                        key={t}
                        className="bg-slate-900/90 backdrop-blur-md text-white text-[11px] font-bold px-2.5 py-1 rounded-lg"
                      >
                        {t}
                      </span>
                    ))}
                  </div>
                </div>
                <div className="p-8 flex-1 flex flex-col justify-between">
                  <div>
                    <div className="flex items-center gap-3 text-xs text-slate-400 mb-3 font-medium">
                      <span className="flex items-center gap-1">
                        <Calendar className="w-3.5 h-3.5 text-indigo-500" />
                        {new Date(date).toLocaleDateString('sk-SK')}
                      </span>
                      <span>•</span>
                      <span className="flex items-center gap-1">
                        <User className="w-3.5 h-3.5" />
                        {author}
                      </span>
                    </div>
                    <h3 className="text-xl font-extrabold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors leading-snug tracking-tight">
                      {article.title}
                    </h3>
                    <p className="mt-3 text-slate-600 dark:text-slate-400 text-sm leading-relaxed line-clamp-3">
                      {desc}
                    </p>
                  </div>
                  <div className="mt-8 pt-4 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs font-bold text-indigo-600 dark:text-indigo-400">
                    <span>Čítať celý článok</span>
                    <span className="group-hover:translate-x-1 transition-transform">→</span>
                  </div>
                </div>
              </button>
            );
          })}
        </div>

        {filteredArticles.length === 0 && (
          <div className="bg-white dark:bg-slate-900 rounded-3xl p-16 text-center border border-slate-200 dark:border-slate-800">
            <h3 className="text-2xl font-bold">Nenašli sa žiadne články</h3>
            <button
              type="button"
              onClick={() => setSelectedTag(null)}
              className="mt-6 bg-indigo-600 text-white font-bold px-6 py-2.5 rounded-xl text-sm"
            >
              Zobraziť všetky
            </button>
          </div>
        )}

        {totalPages > 1 && (
          <div className="flex items-center justify-center gap-3 mt-16">
            <button
              type="button"
              onClick={() => setCurrentPage(1)}
              disabled={!hasPrev}
              className="p-2.5 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
            >
              <ChevronsLeft className="w-5 h-5" />
            </button>
            <button
              type="button"
              onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
              disabled={!hasPrev}
              className="p-2.5 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
            >
              <ChevronLeft className="w-5 h-5" />
            </button>
            {Array.from({ length: totalPages }, (_, i) => i + 1).map((page) => (
              <button
                key={page}
                type="button"
                onClick={() => setCurrentPage(page)}
                className={`w-10 h-10 rounded-xl text-xs font-bold transition-all ${
                  page === currentPage
                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/25'
                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
                }`}
              >
                {page}
              </button>
            ))}
            <button
              type="button"
              onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
              disabled={!hasNext}
              className="p-2.5 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
            >
              <ChevronRight className="w-5 h-5" />
            </button>
            <button
              type="button"
              onClick={() => setCurrentPage(totalPages)}
              disabled={!hasNext}
              className="p-2.5 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
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
