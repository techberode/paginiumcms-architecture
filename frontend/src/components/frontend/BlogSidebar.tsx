import React from 'react';
import { Flame, Hash, Sparkles, Tag } from 'lucide-react';
import type { BlogSidebarPayload } from '../../api/blogSidebar';
import { useI18n } from '../../context/I18nContext';

interface BlogSidebarProps {
  data: BlogSidebarPayload;
  selectedTag: string | null;
  selectedCategory: string | null;
  onSelectTag: (tag: string | null) => void;
  onSelectCategory: (category: string | null) => void;
  onOpenArticle: (slug: string) => void;
  settings: {
    showTags: boolean;
    showCategories: boolean;
    showLatest: boolean;
    showPopular: boolean;
  };
}

function SidebarSection({
  title,
  icon,
  children,
}: {
  title: string;
  icon: React.ReactNode;
  children: React.ReactNode;
}) {
  return (
    <section className="pg-blog-widget">
      <h2 className="pg-blog-widget-title">
        {icon}
        <span>{title}</span>
      </h2>
      <div className="pg-blog-widget-body">{children}</div>
    </section>
  );
}

export const BlogSidebar: React.FC<BlogSidebarProps> = ({
  data,
  selectedTag,
  selectedCategory,
  onSelectTag,
  onSelectCategory,
  onOpenArticle,
  settings,
}) => {
  const { t } = useI18n();

  return (
    <aside className="pg-blog-aside" data-testid="blog-sidebar">
      {settings.showTags && data.tags.length > 0 ? (
        <SidebarSection title={t('public.blog.sidebar.tags')} icon={<Tag className="h-4 w-4" />}>
          <div className="pg-blog-tag-list">
            <button
              type="button"
              onClick={() => onSelectTag(null)}
              className={`pg-blog-tag-chip ${selectedTag === null ? 'is-active' : ''}`}
            >
              {t('public.blog.sidebar.allTags')}
            </button>
            {data.tags.map((tag) => (
              <button
                key={tag}
                type="button"
                onClick={() => onSelectTag(selectedTag === tag ? null : tag)}
                className={`pg-blog-tag-chip ${selectedTag === tag ? 'is-active' : ''}`}
              >
                {tag}
              </button>
            ))}
          </div>
        </SidebarSection>
      ) : null}

      {settings.showCategories && data.categories.length > 0 ? (
        <SidebarSection title={t('public.blog.sidebar.categories')} icon={<Hash className="h-4 w-4" />}>
          <ul className="pg-blog-link-list">
            <li>
              <button
                type="button"
                className={`pg-blog-link-item ${selectedCategory === null ? 'is-active' : ''}`}
                onClick={() => onSelectCategory(null)}
              >
                {t('public.blog.sidebar.allCategories')}
              </button>
            </li>
            {data.categories.map((category) => (
              <li key={category.slug}>
                <button
                  type="button"
                  className={`pg-blog-link-item ${selectedCategory === category.slug ? 'is-active' : ''}`}
                  onClick={() => onSelectCategory(selectedCategory === category.slug ? null : category.slug)}
                >
                  {category.label}
                </button>
              </li>
            ))}
          </ul>
        </SidebarSection>
      ) : null}

      {settings.showPopular && data.popular.length > 0 ? (
        <SidebarSection title={t('public.blog.sidebar.popular')} icon={<Flame className="h-4 w-4" />}>
          <ul className="pg-blog-link-list">
            {data.popular.map((article) => (
              <li key={`popular-${article.slug}`}>
                <button type="button" className="pg-blog-link-item" onClick={() => onOpenArticle(article.slug)}>
                  <span className="pg-blog-link-title">{article.title}</span>
                  {typeof article.views === 'number' && article.views > 0 ? (
                    <span className="pg-blog-link-meta">{t('public.blog.sidebar.views', { count: article.views })}</span>
                  ) : null}
                </button>
              </li>
            ))}
          </ul>
        </SidebarSection>
      ) : null}

      {settings.showLatest && data.latest.length > 0 ? (
        <SidebarSection title={t('public.blog.sidebar.latest')} icon={<Sparkles className="h-4 w-4" />}>
          <ul className="pg-blog-link-list">
            {data.latest.map((article) => (
              <li key={`latest-${article.slug}`}>
                <button type="button" className="pg-blog-link-item" onClick={() => onOpenArticle(article.slug)}>
                  <span className="pg-blog-link-title">{article.title}</span>
                </button>
              </li>
            ))}
          </ul>
        </SidebarSection>
      ) : null}
    </aside>
  );
};
