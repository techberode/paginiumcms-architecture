import React from 'react';
import { FolderTree, HardDrive } from 'lucide-react';
import { Link } from 'react-router-dom';

export interface DashboardFlatFilePanelProps {
  pages: number;
  articles: number;
  media: number;
  backups: number;
  loading?: boolean;
}

export const DashboardFlatFilePanel: React.FC<DashboardFlatFilePanelProps> = ({
  pages,
  articles,
  media,
  backups,
  loading = false,
}) => {
  const rows = [
    { label: 'content/pages/', count: pages, hint: 'home.md, o-nas.md, …' },
    { label: 'content/blog/', count: articles, hint: 'Markdown / JSON články' },
    { label: 'content/config/', count: 2, hint: 'settings.json, nav.json' },
    { label: 'content/media/', count: media, hint: 'Obrázky a prílohy' },
    { label: 'data/backups/', count: backups, hint: 'Zálohy flat-file obsahu' },
  ];

  return (
    <div className="bg-gradient-to-b from-slate-900 to-slate-950 rounded-2xl border border-slate-800 text-white overflow-hidden">
      <div className="px-6 py-4 border-b border-slate-800 flex items-center gap-2">
        <FolderTree className="w-5 h-5 text-indigo-300" />
        <h2 className="text-lg font-semibold">Flat-File štruktúra</h2>
      </div>
      <div className="p-6 space-y-3 font-mono text-sm">
        {rows.map((row) => (
          <div key={row.label} className="rounded-xl bg-slate-900/70 border border-slate-800 px-4 py-3">
            <div className="flex items-center justify-between gap-3">
              <span className="text-indigo-200">{row.label}</span>
              <span className="text-xs font-bold text-slate-300">
                {loading ? '…' : `${row.count} ${row.label.includes('config') ? 'súbory' : 'položiek'}`}
              </span>
            </div>
            <p className="mt-1 text-[11px] text-slate-500">{row.hint}</p>
          </div>
        ))}
        <div className="pt-2 flex flex-col gap-2">
          <Link
            to="/code-editor"
            className="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-bold transition"
          >
            <HardDrive className="w-4 h-4" />
            Preskúmať v inšpektore súborov
          </Link>
          <p className="text-[11px] text-slate-500 text-center">
            Detailný read-only prehliadač: Iterácia 35 (Flat-File Inspector).
          </p>
        </div>
      </div>
    </div>
  );
};

export default DashboardFlatFilePanel;
