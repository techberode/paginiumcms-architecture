// frontend/src/components/frontend/CMSBar.tsx
import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Edit3, PlusCircle, LayoutDashboard, LogOut, Database } from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';
import { useI18n } from '../../context/I18nContext';
import { BTN_PRIMARY } from '../../theme/publicUiClasses';

export interface CMSBarDoc {
  type: 'page' | 'article';
  slug: string;
  title: string;
}

interface CMSBarProps {
  currentDoc?: CMSBarDoc;
}

export const CMSBar: React.FC<CMSBarProps> = ({ currentDoc }) => {
  const { logout } = useAuth();
  const navigate = useNavigate();
  const { t } = useI18n();

  const handleEditCurrent = () => {
    if (!currentDoc) {
      return;
    }
    const base = currentDoc.type === 'page' ? '/pages' : '/articles';
    navigate(`${base}/${currentDoc.slug}`);
  };

  return (
    <div
      className="px-4 py-2 sticky top-0 z-50 shadow-md text-xs font-medium flex flex-wrap items-center justify-between gap-2 border-b"
      style={{
        background: 'color-mix(in srgb, var(--color-text) 92%, var(--color-primary))',
        color: 'var(--color-surface-elevated)',
        borderColor: 'color-mix(in srgb, var(--color-text) 50%, transparent)',
      }}
    >
      <div className="flex items-center gap-3">
        <span className="flex items-center gap-1.5 bg-theme-primary/25 text-theme-accent px-2.5 py-1 rounded-full font-bold">
          <Database className="w-3.5 h-3.5" />
          {t('public.cmsBar.badge')}
        </span>
        <span className="hidden sm:inline opacity-70">{t('public.cmsBar.liveMode')}</span>
      </div>

      <div className="flex items-center gap-2">
        {currentDoc && (
          <button
            type="button"
            onClick={handleEditCurrent}
            className={`flex items-center gap-1.5 px-3 py-1 rounded-lg shadow-sm cursor-pointer font-semibold ${BTN_PRIMARY}`}
          >
            <Edit3 className="w-3.5 h-3.5" />
            {currentDoc.type === 'page' ? t('public.cmsBar.editPage') : t('public.cmsBar.editArticle')}
          </button>
        )}

        <button
          type="button"
          onClick={() => navigate('/articles')}
          className="flex items-center gap-1.5 bg-theme-text/20 hover:bg-theme-text/30 px-3 py-1 rounded-lg border border-theme-primary-foreground/20 transition-colors cursor-pointer"
        >
          <PlusCircle className="w-3.5 h-3.5 text-emerald-400" />
          {t('public.cmsBar.newArticle')}
        </button>

        <button
          type="button"
          onClick={() => navigate('/dashboard')}
          className="flex items-center gap-1.5 bg-theme-text/20 hover:bg-theme-text/30 px-3 py-1 rounded-lg border border-theme-primary-foreground/20 transition-colors cursor-pointer"
        >
          <LayoutDashboard className="w-3.5 h-3.5 text-theme-accent" />
          {t('public.cmsBar.administration')}
        </button>

        <button
          type="button"
          onClick={() => void logout()}
          title={t('public.cmsBar.logout')}
          className="opacity-70 hover:text-rose-400 p-1.5 rounded-lg hover:bg-theme-text/20 transition-colors cursor-pointer ml-1"
        >
          <LogOut className="w-4 h-4" />
        </button>
      </div>
    </div>
  );
};

export default CMSBar;
