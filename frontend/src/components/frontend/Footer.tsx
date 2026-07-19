// frontend/src/components/frontend/Footer.tsx
import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Rocket, ShieldCheck, Zap, Heart, ExternalLink } from 'lucide-react';
import { usePublicSite } from '../../context/PublicSiteContext';
import { useSettingsContext } from '../../context/SettingsContext';

export const Footer: React.FC = () => {
  const { navigation, siteTitle, siteTagline, footerText } = usePublicSite();
  const { settings } = useSettingsContext();
  const navigate = useNavigate();
  const demoUrl = settings.demo?.url ?? 'https://demo.paginiumcms.com';
  const isDemoInstance = settings.demo?.enabled === true;

  const sortedNav = [...navigation].sort((a, b) => a.order - b.order);

  return (
    <footer className="bg-slate-900 text-slate-400 border-t border-slate-800 transition-colors pt-16 pb-12">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-12 pb-16 border-b border-slate-800">
          <div className="md:col-span-1">
            <div className="flex items-center gap-3 text-white mb-4">
              <div className="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center font-bold shadow-lg shadow-indigo-500/25">
                <Rocket className="w-5 h-5 text-white" />
              </div>
              <span className="font-extrabold text-xl tracking-tight text-white">{siteTitle}</span>
            </div>
            <p className="text-sm text-slate-400 leading-relaxed">{siteTagline}</p>
            <div className="mt-6">
              <span className="inline-flex items-center gap-1 text-xs bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2.5 py-1 rounded-full font-semibold">
                <ShieldCheck className="w-3.5 h-3.5" /> Secure FlatFile
              </span>
            </div>
          </div>

          <div>
            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-200 mb-4">Rýchle odkazy</h4>
            <ul className="space-y-2.5">
              {sortedNav.map((item) => (
                <li key={item.id}>
                  <button
                    type="button"
                    onClick={() => navigate(item.path)}
                    className="text-sm hover:text-indigo-400 transition-colors cursor-pointer"
                  >
                    {item.label}
                  </button>
                </li>
              ))}
            </ul>
          </div>

          <div>
            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-200 mb-4">Architektúra</h4>
            <ul className="space-y-3 text-sm text-slate-400">
              <li className="flex items-center gap-2">
                <Zap className="w-4 h-4 text-indigo-400 shrink-0" />
                <span>Flat-file storage bez SQL</span>
              </li>
              <li className="flex items-center gap-2">
                <ShieldCheck className="w-4 h-4 text-emerald-400 shrink-0" />
                <span>Session auth + 2FA admin</span>
              </li>
            </ul>
          </div>

          <div>
            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-200 mb-4">Vyskúšajte CMS</h4>
            {isDemoInstance ? (
              <p className="text-sm text-slate-500 leading-relaxed">
                Ste na demo inštancii. Po skúške sa prostredie resetuje pre ďalších návštevníkov.
              </p>
            ) : (
              <div className="space-y-3">
                <p className="text-sm text-slate-500 leading-relaxed">
                  Open-source flat-file CMS — vyskúšajte admin aj verejný web bez inštalácie.
                </p>
                <a
                  href={demoUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="inline-flex items-center gap-2 text-sm font-semibold text-indigo-400 hover:text-indigo-300"
                >
                  demo.paginiumcms.com
                  <ExternalLink className="w-3.5 h-3.5" />
                </a>
              </div>
            )}
          </div>
        </div>

        <div className="mt-12 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
          <div>{footerText}</div>
          <div className="flex items-center gap-1">
            <span>Navrhnuté s</span>
            <Heart className="w-3.5 h-3.5 text-rose-500 fill-rose-500" />
            <span>pre tvorcov obsahu</span>
          </div>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
