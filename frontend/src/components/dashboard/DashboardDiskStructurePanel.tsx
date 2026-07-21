import React from 'react';
import { BookOpen, Database, FileText, Image as ImageIcon, Users } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';

export interface DashboardDiskStructurePanelProps {
  pages: number;
  articles: number;
  media: number;
  users: number;
  totalHuman?: string | null;
  documentCount?: number;
  loading?: boolean;
}

export const DashboardDiskStructurePanel: React.FC<DashboardDiskStructurePanelProps> = ({
  pages,
  articles,
  media,
  users,
  totalHuman,
  documentCount,
  loading = false,
}) => {
  const { t } = useI18n();

  const rows = [
    { label: t('dashboard.diskStructure.pages'), count: pages, icon: FileText },
    { label: t('dashboard.diskStructure.articles'), count: articles, icon: BookOpen },
    { label: t('dashboard.diskStructure.media'), count: media, icon: ImageIcon },
    { label: t('dashboard.diskStructure.users'), count: users, icon: Users },
  ];

  return (
    <div className="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
      <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center gap-2">
        <Database className="w-5 h-5 text-indigo-500" />
        <h2 className="text-lg font-semibold text-slate-900 dark:text-white">
          {t('dashboard.diskStructure.title')}
        </h2>
      </div>
      <div className="p-6 space-y-3">
        {rows.map((row) => {
          const Icon = row.icon;
          return (
            <div
              key={row.label}
              className="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 dark:border-slate-800 px-4 py-3"
            >
              <div className="flex items-center gap-3 min-w-0">
                <div className="rounded-xl bg-indigo-50 dark:bg-indigo-950/40 p-2 text-indigo-600">
                  <Icon className="w-4 h-4" />
                </div>
                <span className="font-medium text-slate-900 dark:text-white">{row.label}</span>
              </div>
              <span className="text-sm font-black text-slate-700 dark:text-slate-200">
                {loading ? '…' : row.count}
              </span>
            </div>
          );
        })}
        <div className="rounded-2xl bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/40 px-4 py-3 text-sm font-semibold text-indigo-800 dark:text-indigo-200">
          {loading || !totalHuman
            ? '…'
            : t('dashboard.diskStructure.totalContent', {
                size: totalHuman,
                count: documentCount ?? pages + articles,
              })}
        </div>
      </div>
    </div>
  );
};

export default DashboardDiskStructurePanel;
