// frontend/src/components/frontend/CMSBar.tsx
import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Edit3, PlusCircle, LayoutDashboard, LogOut, Database } from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';

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

  const handleEditCurrent = () => {
    if (!currentDoc) {
      return;
    }
    const base = currentDoc.type === 'page' ? '/pages' : '/articles';
    navigate(`${base}/${currentDoc.slug}`);
  };

  return (
    <div className="bg-slate-900 text-slate-100 border-b border-slate-800 px-4 py-2 sticky top-0 z-50 shadow-md text-xs font-medium flex flex-wrap items-center justify-between gap-2">
      <div className="flex items-center gap-3">
        <span className="flex items-center gap-1.5 bg-indigo-500/20 text-indigo-400 px-2.5 py-1 rounded-full font-bold">
          <Database className="w-3.5 h-3.5" />
          Paginium FlatFile Engine
        </span>
        <span className="hidden sm:inline text-slate-400">Režim živej správy</span>
      </div>

      <div className="flex items-center gap-2">
        {currentDoc && (
          <button
            type="button"
            onClick={handleEditCurrent}
            className="flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-1 rounded-lg shadow-sm transition-colors cursor-pointer font-semibold"
          >
            <Edit3 className="w-3.5 h-3.5" />
            Upraviť {currentDoc.type === 'page' ? 'stránku' : 'článok'}
          </button>
        )}

        <button
          type="button"
          onClick={() => navigate('/articles')}
          className="flex items-center gap-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 px-3 py-1 rounded-lg border border-slate-700 transition-colors cursor-pointer"
        >
          <PlusCircle className="w-3.5 h-3.5 text-emerald-400" />
          Nový článok
        </button>

        <button
          type="button"
          onClick={() => navigate('/dashboard')}
          className="flex items-center gap-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 px-3 py-1 rounded-lg border border-slate-700 transition-colors cursor-pointer"
        >
          <LayoutDashboard className="w-3.5 h-3.5 text-indigo-400" />
          Administrácia
        </button>

        <button
          type="button"
          onClick={() => void logout()}
          title="Odhlásiť sa"
          className="text-slate-400 hover:text-rose-400 p-1.5 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer ml-1"
        >
          <LogOut className="w-4 h-4" />
        </button>
      </div>
    </div>
  );
};

export default CMSBar;
