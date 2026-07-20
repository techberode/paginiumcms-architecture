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

type TabId = 'incidents' | 'bans' | 'whitelist';

function formatDate(value: string): string {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return value;
  }
  return date.toLocaleString('sk-SK');
}

function formatExpiry(ban: FirewallBan): string {
  if (ban.permanent) {
    return 'Trvalý';
  }
  if (ban.expires_at === null) {
    return '—';
  }
  return formatDate(new Date(ban.expires_at * 1000).toISOString());
}

export const FirewallManager: React.FC = () => {
  const toast = useToast();
  const openInNewTab = useOpenLinksInNewTab();
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
      toast.error('Nepodarilo sa načítať firewall');
    } finally {
      setLoading(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

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
          { value: 'created_at', label: 'Čas', getValue: (item) => item.created_at },
          { value: 'ip', label: 'IP', getValue: (item) => item.ip },
          { value: 'scenario', label: 'Scenár', getValue: (item) => item.scenario },
        ],
        page: 1,
        pageSize: 100,
      }),
    [incidents, search, sortDirection, sortField]
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
          { value: 'ip', label: 'IP', getValue: (item) => item.ip },
          { value: 'updated_at', label: 'Aktualizované', getValue: (item) => item.updated_at },
          { value: 'score', label: 'Skóre', getValue: (item) => item.score },
        ],
        page: 1,
        pageSize: 100,
      }),
    [bans, search, sortDirection, sortField]
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
          sortFields: [{ value: 'ip', label: 'IP', getValue: (item) => item.ip }],
          page: 1,
          pageSize: 100,
        }
      ),
    [whitelist, search]
  );

  const handleUnban = async (ip: string) => {
    if (!confirm(`Odblokovať IP ${ip}?`)) {
      return;
    }
    setBusyIp(ip);
    try {
      if (await firewallApi.unban(ip)) {
        toast.success(`IP ${ip} odblokovaná`);
        await loadAll();
      } else {
        toast.error('Unban zlyhal');
      }
    } finally {
      setBusyIp(null);
    }
  };

  const handleManualBan = async () => {
    const ip = newIp.trim();
    if (!ip) {
      toast.error('Zadajte IP adresu');
      return;
    }
    setBusyIp(ip);
    try {
      const ban = await firewallApi.createBan(ip, permanentBan);
      if (ban) {
        toast.success(permanentBan ? `Trvalý ban pre ${ip}` : `Dočasný jail pre ${ip}`);
        setNewIp('');
        await loadAll();
      } else {
        toast.error('Ban zlyhal');
      }
    } finally {
      setBusyIp(null);
    }
  };

  const handleAddWhitelist = async () => {
    const ip = newIp.trim();
    if (!ip) {
      toast.error('Zadajte IP adresu');
      return;
    }
    setBusyIp(ip);
    try {
      if (await firewallApi.addWhitelist(ip)) {
        toast.success(`${ip} pridaná na whitelist`);
        setNewIp('');
        await loadAll();
      } else {
        toast.error('Whitelist zlyhal');
      }
    } finally {
      setBusyIp(null);
    }
  };

  const handleRemoveWhitelist = async (ip: string) => {
    if (!confirm(`Odstrániť ${ip} z whitelistu?`)) {
      return;
    }
    setBusyIp(ip);
    try {
      if (await firewallApi.removeWhitelist(ip)) {
        toast.success(`${ip} odstránená z whitelistu`);
        await loadAll();
      } else {
        toast.error('Odstránenie zlyhalo');
      }
    } finally {
      setBusyIp(null);
    }
  };

  const tabs: { id: TabId; label: string; icon: React.ElementType }[] = [
    { id: 'incidents', label: 'Incidenty', icon: ShieldAlert },
    { id: 'bans', label: 'Bany', icon: Ban },
    { id: 'whitelist', label: 'Whitelist', icon: ShieldCheck },
  ];

  return (
    <div className="flex-1 overflow-y-auto bg-slate-50 p-4 md:p-8">
      <div className="max-w-7xl mx-auto space-y-6">
        <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
          <div>
            <h1 className="text-2xl font-black text-slate-900 flex items-center gap-2">
              <Shield className="w-7 h-7 text-indigo-600" />
              Firewall (WAF)
            </h1>
            <p className="text-sm text-slate-500 mt-1">
              Interná ochrana pred probe útokmi, traversal a podozrivými botmi.
            </p>
          </div>
          <Link
            to="/settings"
            state={{ group: 'firewall' }}
            className="inline-flex items-center gap-2 text-sm font-bold text-indigo-600 hover:text-indigo-800"
          >
            Nastavenia firewallu
            <ExternalLink className="w-4 h-4" />
          </Link>
        </div>

        {stats && (
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
            {[
              { label: 'Aktívne jaily', value: stats.active_jails },
              { label: 'Trvalé bany', value: stats.permanent_bans },
              { label: 'Incidenty (24 h)', value: stats.incidents_24h },
              { label: 'Whitelist', value: stats.whitelist_count },
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
            <AdminListToolbar search={search} onSearchChange={setSearch} searchPlaceholder="Hľadať…" />
          </div>

          <div className="p-4 border-b border-slate-100 flex flex-col sm:flex-row gap-3">
            <input
              type="text"
              value={newIp}
              onChange={(e) => setNewIp(e.target.value)}
              placeholder="IP adresa (napr. 203.0.113.10)"
              className="flex-1 rounded-xl border border-slate-200 px-4 py-2 text-sm"
            />
            {tab === 'bans' && (
              <label className="inline-flex items-center gap-2 text-sm text-slate-600">
                <input
                  type="checkbox"
                  checked={permanentBan}
                  onChange={(e) => setPermanentBan(e.target.checked)}
                />
                Trvalý ban
              </label>
            )}
            <button
              type="button"
              disabled={busyIp !== null}
              onClick={() => void (tab === 'whitelist' ? handleAddWhitelist() : handleManualBan())}
              className="px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700 disabled:opacity-50 cursor-pointer"
            >
              {tab === 'whitelist' ? 'Pridať na whitelist' : 'Zablokovať IP'}
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
                        label="Čas"
                        field="created_at"
                        activeField={sortField}
                        direction={sortDirection}
                        onSort={handleSort}
                      />
                      <SortableTableHeader
                        label="IP"
                        field="ip"
                        activeField={sortField}
                        direction={sortDirection}
                        onSort={handleSort}
                      />
                      <SortableTableHeader
                        label="Scenár"
                        field="scenario"
                        activeField={sortField}
                        direction={sortDirection}
                        onSort={handleSort}
                      />
                      <th className="px-4 py-3 text-left text-xs font-bold text-slate-500">URI</th>
                      <th className="px-4 py-3 text-left text-xs font-bold text-slate-500">User-Agent</th>
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
                        label="IP"
                        field="ip"
                        activeField={sortField}
                        direction={sortDirection}
                        onSort={handleSort}
                      />
                      <th className="px-4 py-3 text-left text-xs font-bold text-slate-500">Dôvod</th>
                      <th className="px-4 py-3 text-left text-xs font-bold text-slate-500">Platnosť</th>
                      <SortableTableHeader
                        label="Skóre"
                        field="score"
                        activeField={sortField}
                        direction={sortDirection}
                        onSort={handleSort}
                      />
                      <th className="px-4 py-3 text-right text-xs font-bold text-slate-500">Akcie</th>
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
                            <span className="text-slate-400 text-xs">Expirovaný</span>
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
                              Unban
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
                      <th className="px-4 py-3 text-left text-xs font-bold text-slate-500">IP</th>
                      <th className="px-4 py-3 text-right text-xs font-bold text-slate-500">Akcie</th>
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
                            Odstrániť
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
                <div className="p-12 text-center text-slate-400 text-sm">Žiadne záznamy</div>
              )}
            </div>
          )}
        </div>

        <p className="text-xs text-slate-400">
          Podrobná dokumentácia:{' '}
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
