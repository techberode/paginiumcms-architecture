// frontend/src/components/backend/AdminCommandPalette.tsx
import React, { useCallback, useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Search,
  X,
  FileText,
  BookOpen,
  Image as ImageIcon,
  LayoutDashboard,
  CornerDownLeft,
} from 'lucide-react';
import { searchAdmin, AdminSearchResultItem } from '../../api/search';
import { useI18n } from '../../context/I18nContext';

const RECENT_KEY = 'paginium_admin_search_recent';
const MAX_RECENT = 8;

interface AdminCommandPaletteProps {
  isOpen: boolean;
  onClose: () => void;
}

function loadRecent(): AdminSearchResultItem[] {
  try {
    const raw = localStorage.getItem(RECENT_KEY);
    if (!raw) {
      return [];
    }
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
}

function saveRecent(item: AdminSearchResultItem): void {
  const current = loadRecent().filter((row) => row.adminPath !== item.adminPath || row.title !== item.title);
  const next = [item, ...current].slice(0, MAX_RECENT);
  localStorage.setItem(RECENT_KEY, JSON.stringify(next));
}

function typeIcon(type: AdminSearchResultItem['type']) {
  switch (type) {
    case 'article':
      return BookOpen;
    case 'media':
      return ImageIcon;
    case 'route':
      return LayoutDashboard;
    default:
      return FileText;
  }
}

export const AdminCommandPalette: React.FC<AdminCommandPaletteProps> = ({ isOpen, onClose }) => {
  const { t } = useI18n();
  const navigate = useNavigate();
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<AdminSearchResultItem[]>([]);
  const [loading, setLoading] = useState(false);
  const [activeIndex, setActiveIndex] = useState(0);
  const [recent, setRecent] = useState<AdminSearchResultItem[]>([]);

  const typeLabel = (type: AdminSearchResultItem['type']): string => {
    switch (type) {
      case 'article':
        return t('platform.commandPalette.types.article');
      case 'media':
        return t('platform.commandPalette.types.media');
      case 'route':
        return t('platform.commandPalette.types.route');
      default:
        return t('platform.commandPalette.types.page');
    }
  };

  useEffect(() => {
    if (isOpen) {
      setRecent(loadRecent());
    }
  }, [isOpen]);

  useEffect(() => {
    if (!isOpen) {
      setQuery('');
      setResults([]);
      setActiveIndex(0);
    }
  }, [isOpen]);

  useEffect(() => {
    if (!isOpen || query.trim().length < 2) {
      setResults([]);
      setActiveIndex(0);
      return;
    }

    let cancelled = false;
    const timer = window.setTimeout(async () => {
      setLoading(true);
      try {
        const payload = await searchAdmin(query.trim(), {
          types: ['page', 'article', 'media', 'route'],
          limit: 8,
        });
        if (!cancelled) {
          setResults(payload?.results ?? []);
          setActiveIndex(0);
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    }, 200);

    return () => {
      cancelled = true;
      window.clearTimeout(timer);
    };
  }, [isOpen, query]);

  const visibleItems = query.trim().length >= 2 ? results : recent;

  const selectItem = useCallback(
    (item: AdminSearchResultItem) => {
      saveRecent(item);
      navigate(item.adminPath);
      onClose();
    },
    [navigate, onClose]
  );

  useEffect(() => {
    if (!isOpen) {
      return;
    }

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        onClose();
        return;
      }

      if (visibleItems.length === 0) {
        return;
      }

      if (event.key === 'ArrowDown') {
        event.preventDefault();
        setActiveIndex((index) => (index + 1) % visibleItems.length);
      } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        setActiveIndex((index) => (index - 1 + visibleItems.length) % visibleItems.length);
      } else if (event.key === 'Enter') {
        event.preventDefault();
        const item = visibleItems[activeIndex];
        if (item) {
          selectItem(item);
        }
      }
    };

    window.addEventListener('keydown', onKeyDown);
    return () => window.removeEventListener('keydown', onKeyDown);
  }, [activeIndex, isOpen, onClose, selectItem, visibleItems]);

  if (!isOpen) {
    return null;
  }

  return (
    <div
      className="fixed inset-0 z-[70] flex items-start justify-center pt-16 sm:pt-24 px-4 bg-slate-900/80 backdrop-blur-sm"
      onClick={onClose}
      role="presentation"
    >
      <div
        className="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-2xl w-full border border-slate-200 dark:border-slate-800 overflow-hidden"
        onClick={(event) => event.stopPropagation()}
        role="dialog"
        aria-modal="true"
        aria-label={t('platform.commandPalette.ariaLabel')}
      >
        <div className="flex items-center px-5 py-4 border-b border-slate-100 dark:border-slate-800 gap-3">
          <Search className="w-5 h-5 text-indigo-500" />
          <input
            type="text"
            autoFocus
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            placeholder={t('platform.commandPalette.placeholder')}
            className="flex-1 bg-transparent text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none text-base"
          />
          <button
            type="button"
            onClick={onClose}
            className="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg"
            aria-label={t('platform.commandPalette.close')}
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        <div className="max-h-[55vh] overflow-y-auto p-2">
          {query.trim().length < 2 && recent.length > 0 && (
            <div className="px-3 py-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">
              {t('platform.commandPalette.recent')}
            </div>
          )}

          {query.trim().length >= 2 && !loading && visibleItems.length === 0 && (
            <div className="py-10 text-center text-slate-500 dark:text-slate-400 text-sm">
              {t('platform.commandPalette.noResults', { query: query.trim() })}
            </div>
          )}

          {visibleItems.map((item, index) => {
            const Icon = typeIcon(item.type);
            const active = index === activeIndex;
            return (
              <button
                key={`${item.type}:${item.adminPath}:${item.title}:${index}`}
                type="button"
                onMouseEnter={() => setActiveIndex(index)}
                onClick={() => selectItem(item)}
                className={`w-full text-left px-4 py-3 rounded-xl flex items-start gap-3 transition-colors ${
                  active
                    ? 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-200'
                    : 'hover:bg-slate-50 dark:hover:bg-slate-800/60'
                }`}
              >
                <Icon className="w-5 h-5 mt-0.5 shrink-0" />
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2">
                    <span className="font-semibold truncate">{item.title}</span>
                    <span className="text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500">
                      {typeLabel(item.type)}
                    </span>
                  </div>
                  {item.subtitle && (
                    <p className="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5">{item.subtitle}</p>
                  )}
                </div>
                {active && <CornerDownLeft className="w-4 h-4 shrink-0 opacity-60" />}
              </button>
            );
          })}
        </div>

        <div className="px-5 py-3 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-500 flex items-center justify-between">
          <span>{t('platform.commandPalette.footer')}</span>
          <span>
            {loading
              ? t('platform.commandPalette.searching')
              : t('platform.commandPalette.itemCount', { count: visibleItems.length })}
          </span>
        </div>
      </div>
    </div>
  );
};

export default AdminCommandPalette;
