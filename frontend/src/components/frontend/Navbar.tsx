// frontend/src/components/frontend/Navbar.tsx
import React, { useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { Search, Shield, Menu, X, Rocket } from 'lucide-react';
import { usePublicSite } from '../../context/PublicSiteContext';
import { useAuth } from '../../hooks/useAuth';

interface NavbarProps {
  onOpenSearch: () => void;
}

export const Navbar: React.FC<NavbarProps> = ({ onOpenSearch }) => {
  const { navigation, siteTitle } = usePublicSite();
  const { user } = useAuth();
  const location = useLocation();
  const navigate = useNavigate();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const isPathActive = (navPath: string) => {
    if (navPath === '/') {
      return location.pathname === '/';
    }
    return location.pathname.startsWith(navPath);
  };

  const sortedNav = [...navigation].sort((a, b) => a.order - b.order);

  return (
    <header className="bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 sticky top-0 z-40 transition-colors">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
        <button
          type="button"
          onClick={() => navigate('/')}
          className="flex items-center gap-3 cursor-pointer group"
        >
          <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-transform">
            <Rocket className="w-5 h-5 text-white" />
          </div>
          <div className="text-left">
            <span className="font-extrabold text-xl tracking-tight text-slate-900 dark:text-white block">
              {siteTitle}
            </span>
            <span className="hidden sm:block text-[10px] font-semibold tracking-wider text-indigo-500 uppercase">
              FlatFile Architecture
            </span>
          </div>
        </button>

        <nav className="hidden md:flex items-center gap-1">
          {sortedNav.map((item) => {
            const active = isPathActive(item.path);
            return (
              <button
                key={item.id}
                type="button"
                onClick={() => navigate(item.path)}
                className={`px-4 py-2 rounded-xl text-sm font-semibold transition-all cursor-pointer ${
                  active
                    ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/50'
                    : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60'
                }`}
              >
                {item.label}
              </button>
            );
          })}
        </nav>

        <div className="flex items-center gap-2 sm:gap-3">
          <button
            type="button"
            onClick={onOpenSearch}
            className="flex items-center gap-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 px-3.5 py-2 rounded-xl text-xs font-semibold transition-colors cursor-pointer"
          >
            <Search className="w-4 h-4 text-indigo-500" />
            <span className="hidden sm:inline text-slate-500 dark:text-slate-400 font-normal">Hľadať…</span>
          </button>

          {user ? (
            <button
              type="button"
              onClick={() => navigate('/dashboard')}
              className="flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-lg shadow-indigo-500/25 transition-all cursor-pointer"
            >
              <Shield className="w-4 h-4" />
              <span className="hidden sm:inline">Paginium Admin</span>
            </button>
          ) : (
            <button
              type="button"
              onClick={() => navigate('/login')}
              className="flex items-center gap-1.5 bg-slate-900 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 dark:text-slate-900 text-white px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-sm cursor-pointer"
            >
              <Shield className="w-3.5 h-3.5" />
              <span className="hidden sm:inline">Prihlásiť</span>
            </button>
          )}

          <button
            type="button"
            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
            className="md:hidden p-2 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors"
          >
            {mobileMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
          </button>
        </div>
      </div>

      {mobileMenuOpen && (
        <div className="md:hidden border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-6 shadow-xl animate-fadeIn">
          <div className="flex flex-col gap-2">
            {sortedNav.map((item) => (
              <button
                key={item.id}
                type="button"
                onClick={() => {
                  navigate(item.path);
                  setMobileMenuOpen(false);
                }}
                className={`p-3 rounded-xl text-base font-bold text-left ${
                  isPathActive(item.path)
                    ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400'
                    : 'text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800'
                }`}
              >
                {item.label}
              </button>
            ))}
          </div>
        </div>
      )}
    </header>
  );
};

export default Navbar;
