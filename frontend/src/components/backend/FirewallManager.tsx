// frontend/src/components/backend/FirewallManager.tsx
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  Ban,
  ExternalLink,
  Loader2,
  Shield,
  ShieldAlert,
  ShieldCheck,
  Trash2,
} from 'lucide-react';
import { useOpenLinksInNewTab } from '../../hooks/useOpenLinksInNewTab';
import { linkTargetProps } from '../../utils/linkTarget';
import { settingsGroupPath } from '../../utils/adminDeepLinks';
import {
  firewallApi,
  type FirewallBan,
  type FirewallIncident,
  type FirewallStats,
} from '../../api/firewall';
import { useToast } from '../../hooks/useToast';
import { useColumnSort } from '../../hooks/useColumnSort';
import { SortableTableHeader } from './SortableTableHeader';
import { AdminListToolbar } from './AdminListToolbar';
import { applyClientListView } from '../../utils/clientListView';
import { useI18n } from '../../context/I18nContext';

type TabId = 'incidents' | 'bans' | 'whitelist';

export const FirewallManager: React.FC = () => {
  const { t, locale } = useI18n();
  const toast = useToast();
  const openInNewTab = useOpenLinksInNewTab();
  const dateLocale = locale === 'en' ? 'en-US' : 'sk-SK';

  const formatDate = useCallback(
    (value: string): string => {
      const date = new Date(value);
      if (Number.isNaN(date.getTime())) {
        return value;
      }
      return date.toLocaleString(dateLocale);
    },
    [dateLocale]
  );

  const formatExpiry = useCallback(
    (ban: FirewallBan): string => {
      if (ban.permanent) {
        return t('platform.firewall.permanent');
      }
      if (ban.expires_at === null) {
        return '—';
      }
      return formatDate(new Date(ban.expires_at * 1000).toISOString());
    },
    [formatDate, t]
  );

  const [tab, setTab] = useState<TabId>('incidents');
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState<FirewallStats | null>(null);
  const [incidents, setIncidents] = useState<FirewallIncident[]>([]);
  const [bans, setBans] = useState<FirewallBan[]>([]);
  const [whitelist, setWhitelist] = useState<string[]>([]);
  const [search, setSearch] = useState('');
  const [newIp, setNewIp] = useState('');
  const [permanentBan, setPermanentBan] = useState(false);
  const [busyIp, setBusyIp] = useState<string | null>(null);
  const { sortField, sortDirection, handleSort } = useColumnSort('created_at', 'desc');

  const loadAll = useCallback(async () => {
    setLoading(true);
    try {
      const [statsData, incidentsData, bansData, whitelistData] = await Promise.all([
        firewallApi.stats(),
        firewallApi.incidents(100, 0),
        firewallApi.bans(true),
        firewallApi.whitelist(),
      ]);
      setStats(statsData);
      setIncidents(incidentsData?.items ?? []);
      setBans(bansData);
      setWhitelist(whitelistData);
    } catch {
      toast.error(t('platform.firewall.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [t]);

  useEffect(() => {
    void loadAll();
  }, [loadAll]);

  const incidentView = useMemo(
    () =>
      applyClientListView(incidents, {
        search,
        searchText: (item) =>
          `${item.ip} ${item.scenario} ${item.uri} ${item.user_agent} ${formatDate(item.created_at)}`,
        sortField,
        sortDirection,
        sortFields: [
          { value: 'created_at', label: t('platform.firewall.columns.time'), getValue: (item) => item.created_at },
          { value: 'ip', label: t('platform.firewall.columns.ip'), getValue: (item) => item.ip },
          { value: 'scenario', label: t('platform.firewall.columns.scenario'), getValue: (item) => item.scenario },
        ],
        page: 1,
        pageSize: 100,
      }),
    [incidents, search, sortDirection, sortField, formatDate, t]
  );

  const banView = useMemo(
    () =>
      applyClientListView(bans, {
        search,
        searchText: (item) =>
          `${item.ip} ${item.reason} ${formatExpiry(item)} ${item.score}`,
        sortField,
        sortDirection,
        sortFields: [
          { value: 'ip', label: t('platform.firewall.columns.ip'), getValue: (item) => item.ip },
          {
            value: 'updated_at',
            label: t('platform.firewall.columns.updated'),
            getValue: (item) => item.updated_at,
          },
          { value: 'score', label: t('platform.firewall.columns.score'), getValue: (item) => item.score },
        ],
        page: 1,
        pageSize: 100,
      }),
    [bans, search, sortDirection, sortField, formatExpiry, t]
  );

  const whitelistView = useMemo(
    () =>
      applyClientListView(
        whitelist.map((ip) => ({ ip })),
        {
          search,
          searchText: (item) => item.ip,
          sortField: 'ip',
          sortDirection: 'asc',
          sortFields: [{ value: 'ip', label: t('platform.firewall.columns.ip'), getValue: (item) => item.ip }],
          page: 1,
          pageSize: 100,
        }
      ),
    [whitelist, search, t]
  );

  const handleUnban = async (ip: string) => {
    if (!confirm(t('platform.firewall.toast.unbanConfirm', { ip }))) {
      return;
    }
    setBusyIp(ip);
    try {
      if (await firewallApi.unban(ip)) {
        toast.success(t('platform.firewall.toast.unbanSuccess', { ip }));
        await loadAll();
      } else {
        toast.error(t('platform.firewall.toast.unbanFailed'));
      }
    } finally {
      setBusyIp(null);
    }
  };

  const handleManualBan = async () => {
    const ip = newIp.trim();
    if (!ip) {
      toast.error(t('platform.firewall.toast.ipRequired'));
      return;
    }
    setBusyIp(ip);
    try {
      const ban = await firewallApi.createBan(ip, permanentBan);
      if (ban) {
        toast.success(
          permanentBan
            ? t('platform.firewall.toast.permanentBanSuccess', { ip })
            : t('platform.firewall.toast.tempBanSuccess', { ip })
        );
        setNewIp('');
        await loadAll();
      } else {
        toast.error(t('platform.firewall.toast.banFailed'));
      }
    } finally {
      setBusyIp(null);
    }
  };

  const handleAddWhitelist = async () => {
    const ip = newIp.trim();
    if (!ip) {
      toast.error(t('platform.firewall.toast.ipRequired'));
      return;
    }
    setBusyIp(ip);
    try {
      if (await firewallApi.addWhitelist(ip)) {
        toast.success(t('platform.firewall.toast.whitelistAdded', { ip }));
        setNewIp('');
        await loadAll();
      } else {
        toast.error(t('platform.firewall.toast.whitelistFailed'));
      }
    } finally {
      setBusyIp(null);
    }
  };

  const handleRemoveWhitelist = async (ip: string) => {
    if (!confirm(t('platform.firewall.toast.whitelistRemoveConfirm', { ip }))) {
      return;
    }
    setBusyIp(ip);
    try {
      if (await firewallApi.removeWhitelist(ip)) {
        toast.success(t('platform.firewall.toast.whitelistRemoved', { ip }));
        await loadAll();
      } else {
        toast.error(t('platform.firewall.toast.removeFailed'));
      }
    } finally {
      setBusyIp(null);
    }
  };

  const tabs: { id: TabId; label: string; icon: React.ElementType }[] = [
    { id: 'incidents', label: t('platform.firewall.tabs.incidents'), icon: ShieldAlert },
    { id: 'bans', label: t('platform.firewall.tabs.bans'), icon: Ban },
    { id: 'whitelist', label: t('platform.firewall.tabs.whitelist'), icon: ShieldCheck },
  ];

  return (
    <div className="flex-1 overflow-y-auto bg-slate-50 p-4 md:p-8">
      <div className="max-w-7xl mx-auto space-y-6">
        <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
          <div>
            <h1 className="text-2xl font-black text-slate-900 flex items-center gap-2">
              <Shield className="w-7 h-7 text-indigo-600" />
              {t('platform.firewall.title')}
            </h1>
            <p className="text-sm text-slate-500 mt-1">{t('platform.firewall.subtitle')}</p>
          </div>
          <Link
            to={settingsGroupPath('firewall')}
            className="inline-flex items-center gap-2 text-sm font-bold text-indigo-600 hover:text-indigo-800"
          >
            {t('platform.firewall.settingsLink')}
            <ExternalLink className="w-4 h-4" />
          </Link>
        </div>

        {stats && (
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
            {[
              { label: t('platform.firewall.stats.activeJails'), value: stats.active_jails },
              { label: t('platform.firewall.stats.permanentBans'), value: stats.permanent_bans },
              { label: t('platform.firewall.stats.incidents24h'), value: stats.incidents_24h },
              { label: t('platform.firewall.stats.whitelist'), value: stats.whitelist_count },
            ].map((card) => (
              <div
                key={card.label}
                className="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm"
              >
                <div className="text-xs font-bold text-slate-400 uppercase tracking-wide">
                  {card.label}
                </div>
                <div className="text-2xl font-black text-slate-900 mt-1">{card.value}</div>
              </div>
            ))}
          </div>
        )}

        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="flex flex-wrap gap-2 p-4 border-b border-slate-100">
            {tabs.map(({ id, label, icon: Icon }) => (
              <button
                key={id}
                type="button"
                onClick={() => setTab(id)}
                className={`inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-colors cursor-pointer ${
                  tab === id
                    ? 'bg-indigo-600 text-white'
                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                }`}
              >
                <Icon className="w-4 h-4" />
                {label}
              </button>
            ))}
          </div>

          <div className="p-4 border-b border-slate-100">
            <AdminListToolbar
              search={search}
              onSearchChange={setSearch}
              searchPlaceholder={t('platform.firewall.searchPlaceholder')}
            />
          </div>

          <div className="p-4 border-b border-slate-100 flex flex-col sm:flex-row gap-3">
            <input
              type="text"
              value={newIp}
              onChange={(e) => setNewIp(e.target.value)}
              placeholder={t('platform.firewall.ipPlaceholder')}
              className="flex-1 rounded-xl border border-slate-200 px-4 py-2 text-sm"
            />
            {tab === 'bans' && (
              <label className="inline-flex items-center gap-2 text-sm text-slate-600">
                <input
                  type="checkbox"
                  checked={permanentBan}
                  onChange={(e) => setPermanentBan(e.target.checked)}
                />
                {t('platform.firewall.permanentBan')}
              </label>
            )}
            <button
              type="button"
              disabled={busyIp !== null}
              onClick={() => void (tab === 'whitelist' ? handleAddWhitelist() : handleManualBan())}
              className="px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700 disabled:opacity-50 cursor-pointer"
            >
              {tab === 'whitelist' ? t('platform.firewall.addWhitelist') : t('platform.firewall.blockIp')}
            </button>
          </div>

          {loading ? (
            <div className="p-12 flex justify-center text-slate-400">
              <Loader2 className="w-8 h-8 animate-spin" />
            </div>
          ) : (
            <div className="overflow-x-auto">
              {tab === 'incidents' && (
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-slate-100 bg-slate-50/80">
                      <SortableTableHeader
                        label={t('platform.firewall.columns.time')}
                        field="created_at"
                        activeField={sortField}
                        direction={sortDirection}
                        onSort={handleSort}
                      />
                      <SortableTableHeader
                        label={t('platform.firewall.columns.ip')}
                        field="ip"
                        activeField={sortField}
                        direction={sortDirection}
                        onSort={handleSort}
                      />
                      <SortableTableHeader
                        label={t('platform.firewall.columns.scenario')}
                        field="scenario"
                        activeField={sortField}
                        direction={sortDirection}
                        onSort={handleSort}
                      />
                      <th className="px-4 py-3 text-left text-xs font-bold text-slate-500">
                        {t('platform.firewall.columns.uri')}
                      </th>
                      <th className="px-4 py-3 text-left text-xs font-bold text-slate-500">
                        {t('platform.firewall.columns.userAgent')}
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    {incidentView.items.map((item) => (
                      <tr key={item.id} className="border-b border-slate-50 hover:bg-slate-50/50">
                        <td className="px-4 py-3 whitespace-nowrap">{formatDate(item.created_at)}</td>
                        <td className="px-4 py-3 font-mono text-xs">{item.ip}</td>
                        <td className="px-4 py-3">
                          <span className="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-xs font-bold">
                            {item.scenario}
                          </span>
                        </td>
                        <td className="px-4 py-3 max-w-xs truncate font-mono text-xs" title={item.uri}>
                          {item.uri}
                        </td>
                        <td className="px-4 py-3 max-w-xs truncate text-xs text-slate-500" title={item.user_agent}>
                          {item.user_agent || '—'}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}

              {tab === 'bans' && (
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-slate-100 bg-slate-50/80">
                      <SortableTableHeader
                        label={t('platform.firewall.columns.ip')}
                        field="ip"
                        activeField={sortField}
                        direction={sortDirection}
                        onSort={handleSort}
                      />
                      <th className="px-4 py-3 text-left text-xs font-bold text-slate-500">
                        {t('platform.firewall.columns.reason')}
                      </th>
                      <th className="px-4 py-3 text-left text-xs font-bold text-slate-500">
                        {t('platform.firewall.columns.validity')}
                      </th>
                      <SortableTableHeader
                        label={t('platform.firewall.columns.score')}
                        field="score"
                        activeField={sortField}
                        direction={sortDirection}
                        onSort={handleSort}
                      />
                      <th className="px-4 py-3 text-right text-xs font-bold text-slate-500">
                        {t('platform.firewall.columns.actions')}
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    {banView.items.map((item) => (
                      <tr key={item.ip} className="border-b border-slate-50 hover:bg-slate-50/50">
                        <td className="px-4 py-3 font-mono text-xs">{item.ip}</td>
                        <td className="px-4 py-3">{item.reason}</td>
                        <td className="px-4 py-3">
                          {item.active ? (
                            <span
                              className={`px-2 py-0.5 rounded-full text-xs font-bold ${
                                item.permanent
                                  ? 'bg-red-100 text-red-800'
                                  : 'bg-orange-100 text-orange-800'
                              }`}
                            >
                              {formatExpiry(item)}
                            </span>
                          ) : (
                            <span className="text-slate-400 text-xs">{t('platform.firewall.expired')}</span>
                          )}
                        </td>
                        <td className="px-4 py-3">{item.score}</td>
                        <td className="px-4 py-3 text-right">
                          {item.active && (
                            <button
                              type="button"
                              disabled={busyIp === item.ip}
                              onClick={() => void handleUnban(item.ip)}
                              className="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-bold text-indigo-600 hover:bg-indigo-50 cursor-pointer disabled:opacity-50"
                            >
                              <Trash2 className="w-3.5 h-3.5" />
                              {t('platform.firewall.unban')}
                            </button>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}

              {tab === 'whitelist' && (
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-slate-100 bg-slate-50/80">
                      <th className="px-4 py-3 text-left text-xs font-bold text-slate-500">
                        {t('platform.firewall.columns.ip')}
                      </th>
                      <th className="px-4 py-3 text-right text-xs font-bold text-slate-500">
                        {t('platform.firewall.columns.actions')}
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    {whitelistView.items.map((item) => (
                      <tr key={item.ip} className="border-b border-slate-50 hover:bg-slate-50/50">
                        <td className="px-4 py-3 font-mono text-xs">{item.ip}</td>
                        <td className="px-4 py-3 text-right">
                          <button
                            type="button"
                            disabled={busyIp === item.ip}
                            onClick={() => void handleRemoveWhitelist(item.ip)}
                            className="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-bold text-red-600 hover:bg-red-50 cursor-pointer disabled:opacity-50"
                          >
                            {t('platform.firewall.remove')}
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}

              {((tab === 'incidents' && incidentView.items.length === 0) ||
                (tab === 'bans' && banView.items.length === 0) ||
                (tab === 'whitelist' && whitelistView.items.length === 0)) && (
                <div className="p-12 text-center text-slate-400 text-sm">{t('platform.firewall.empty')}</div>
              )}
            </div>
          )}
        </div>

        <p className="text-xs text-slate-400">
          {t('platform.firewall.docsHint')}{' '}
          <a
            href="/docs/user/FIREWALL.md"
            className="text-indigo-600 hover:underline"
            {...linkTargetProps(openInNewTab)}
          >
            docs/user/FIREWALL.md
          </a>
        </p>
      </div>
    </div>
  );
};

export default FirewallManager;
