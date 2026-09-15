import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { BookOpen, Headphones, Plus, RefreshCw, Trash2, Users2, Wrench } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { teamsApi, type Team, type TeamMember, type TeamType } from '../../api/teams';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
import { AdminHintCard } from './AdminHintCard';
import { AdminOfferCard } from '../ui/AdminOfferCard';
import { AdminWidgetCard } from '../ui/AdminWidgetCard';
import { AdminFormActions } from './AdminFormActions';
import { AdminTabs } from '../ui/AdminTabs';
import { ADMIN_INPUT, ADMIN_PAGE_SUBTITLE, ADMIN_PAGE_TITLE } from '../../theme/adminUiClasses';

const TEAM_ICONS: Record<TeamType, LucideIcon> = {
  editorial: BookOpen,
  support: Headphones,
  ops: Wrench,
  custom: Users2,
};

const EMPTY_DRAFT = {
  name: '',
  type: 'editorial' as TeamType,
  memberUserIds: [] as string[],
};

export const TeamsManager: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [teams, setTeams] = useState<Team[]>([]);
  const [users, setUsers] = useState<TeamMember[]>([]);
  const [typeFilter, setTypeFilter] = useState<TeamType | 'all'>('all');
  const [selectedId, setSelectedId] = useState<string | null>(null);
  const [creating, setCreating] = useState(false);
  const [draft, setDraft] = useState(EMPTY_DRAFT);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await teamsApi.list();
      setTeams(data.teams);
      setUsers(data.users);
    } catch {
      toast.error(t('platform.teams.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [toast, t]);

  useEffect(() => {
    void load();
  }, [load]);

  const visibleTeams = useMemo(
    () => (typeFilter === 'all' ? teams : teams.filter((team) => team.type === typeFilter)),
    [teams, typeFilter]
  );

  const selected = teams.find((team) => team.id === selectedId) ?? null;

  const startCreate = () => {
    setCreating(true);
    setSelectedId(null);
    setDraft(EMPTY_DRAFT);
  };

  const isPresetName = (value: string): boolean => {
    const trimmed = value.trim();
    return (
      trimmed === '' ||
      trimmed === 'Editorial' ||
      trimmed === 'Support' ||
      trimmed === 'Ops' ||
      trimmed === t('platform.teams.types.editorial') ||
      trimmed === t('platform.teams.types.support') ||
      trimmed === t('platform.teams.types.ops')
    );
  };

  const changeType = (next: TeamType) => {
    setDraft((current) => ({
      ...current,
      type: next,
      name: next === 'custom' && isPresetName(current.name) ? '' : current.name,
    }));
  };

  const openTeam = (team: Team) => {
    setCreating(false);
    setSelectedId(team.id);
    setDraft({
      name: team.type === 'custom' ? team.name : '',
      type: team.type,
      memberUserIds: [...team.memberUserIds],
    });
  };

  const toggleMember = (userId: string) => {
    setDraft((current) => {
      const has = current.memberUserIds.includes(userId);
      return {
        ...current,
        memberUserIds: has
          ? current.memberUserIds.filter((id) => id !== userId)
          : [...current.memberUserIds, userId],
      };
    });
  };

  const handleSave = async () => {
    if (draft.type === 'custom' && !draft.name.trim()) {
      toast.error(t('platform.teams.toast.nameRequired'));
      return;
    }

    setSaving(true);
    try {
      const payload = {
        name: draft.type === 'custom' ? draft.name.trim() : '',
        type: draft.type,
        memberUserIds: draft.memberUserIds,
      };
      const response = creating
        ? await teamsApi.create(payload)
        : selected
          ? await teamsApi.update(selected.id, payload)
          : null;
      if (!response?.success || !response.data?.team) {
        toast.error(response?.error || response?.message || t('platform.teams.toast.saveFailed'));
        return;
      }
      toast.success(creating ? t('platform.teams.toast.created') : t('platform.teams.toast.updated'));
      setCreating(false);
      setSelectedId(response.data.team.id);
      await load();
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (team: Team) => {
    if (!window.confirm(t('platform.teams.confirmDelete', { name: teamTitle(team) }))) {
      return;
    }
    const ok = await teamsApi.remove(team.id);
    if (!ok) {
      toast.error(t('platform.teams.toast.deleteFailed'));
      return;
    }
    toast.success(t('platform.teams.toast.deleted'));
    if (selectedId === team.id) {
      setSelectedId(null);
      setCreating(false);
      setDraft(EMPTY_DRAFT);
    }
    await load();
  };

  const typeLabel = (type: TeamType) => t(`platform.teams.types.${type}`);
  const teamTitle = (team: Team) => (team.type === 'custom' ? team.name : typeLabel(team.type));
  const editing = creating || selected !== null;

  return (
    <div className="p-6 space-y-6" data-testid="teams-manager">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className={`${ADMIN_PAGE_TITLE} flex items-center gap-2`}>
            <Users2 className="w-7 h-7 text-admin-primary" />
            {t('platform.teams.title')}
          </h1>
          <p className={ADMIN_PAGE_SUBTITLE}>{t('platform.teams.subtitle')}</p>
        </div>
        <div className="flex gap-2">
          <button
            type="button"
            onClick={() => void load()}
            className="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-admin-border text-sm text-admin-text"
          >
            <RefreshCw className="w-4 h-4" />
            {t('platform.teams.refresh')}
          </button>
          <button
            type="button"
            onClick={startCreate}
            className="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-admin-primary text-white text-sm"
          >
            <Plus className="w-4 h-4" />
            {t('platform.teams.create')}
          </button>
        </div>
      </div>

      <AdminHintCard title={t('platform.teams.hintTitle')}>{t('platform.teams.hint')}</AdminHintCard>

      <AdminTabs
        ariaLabel={t('platform.teams.filterAll')}
        activeId={typeFilter}
        onSelect={(id) => setTypeFilter(id as typeof typeFilter)}
        items={[
          { id: 'all', label: t('platform.teams.filterAll') },
          ...(['editorial', 'support', 'ops', 'custom'] as const).map((type) => ({
            id: type,
            label: typeLabel(type),
          })),
        ]}
      />

      <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] items-start">
        <div className="grid gap-3 sm:grid-cols-2">
          {loading ? (
            <p className="text-sm text-admin-muted sm:col-span-2">{t('platform.teams.loading')}</p>
          ) : visibleTeams.length === 0 ? (
            <p className="text-sm text-admin-muted sm:col-span-2">{t('platform.teams.empty')}</p>
          ) : (
            visibleTeams.map((team) => (
              <AdminOfferCard
                key={team.id}
                active={selectedId === team.id}
                icon={TEAM_ICONS[team.type]}
                title={teamTitle(team)}
                subtitle={`${team.type === 'custom' ? `${typeLabel('custom')} · ` : ''}${t('platform.teams.memberCount', { count: String(team.memberUserIds.length) })}`}
                testId={`team-card-${team.id}`}
                onSelect={() => openTeam(team)}
                action={
                  <button
                    type="button"
                    className="p-1 rounded text-admin-muted hover:text-rose-600"
                    onClick={(event) => {
                      event.stopPropagation();
                      void handleDelete(team);
                    }}
                    aria-label={t('platform.teams.delete')}
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                }
              />
            ))
          )}
        </div>

        {editing ? (
          <AdminWidgetCard title={creating ? t('platform.teams.create') : t('platform.teams.edit')}>
            <div className="space-y-4">
              <label className="block text-sm">
                <span className="text-admin-muted">{t('platform.teams.type')}</span>
                <select
                  data-testid="team-type"
                  value={draft.type}
                  onChange={(event) => changeType(event.target.value as TeamType)}
                  className={`mt-1 ${ADMIN_INPUT}`}
                >
                  {(['editorial', 'support', 'ops', 'custom'] as const).map((type) => (
                    <option key={type} value={type}>
                      {typeLabel(type)}
                    </option>
                  ))}
                </select>
              </label>
              {draft.type === 'custom' ? (
                <label className="block text-sm">
                  <span className="text-admin-muted">{t('platform.teams.name')}</span>
                  <input
                    data-testid="team-name"
                    value={draft.name}
                    onChange={(event) => setDraft((current) => ({ ...current, name: event.target.value }))}
                    placeholder={t('platform.teams.namePlaceholder')}
                    className={`mt-1 ${ADMIN_INPUT}`}
                  />
                  <span className="mt-1 block text-xs text-admin-muted">{t('platform.teams.nameHint')}</span>
                </label>
              ) : null}
              <fieldset>
                <legend className="text-sm text-admin-muted mb-2">{t('platform.teams.members')}</legend>
                {users.length === 0 ? (
                  <p className="text-sm text-admin-muted">{t('platform.teams.noUsers')}</p>
                ) : (
                  <ul className="max-h-64 overflow-y-auto space-y-1">
                    {users.map((user) => (
                      <li key={user.id}>
                        <label className="flex items-center gap-2 rounded-lg border border-admin-border bg-admin-canvas px-3 py-2 text-sm text-admin-text">
                          <input
                            type="checkbox"
                            data-testid={`team-member-${user.id}`}
                            checked={draft.memberUserIds.includes(user.id)}
                            onChange={() => toggleMember(user.id)}
                          />
                          <span>
                            {user.name}
                            <span className="text-admin-muted"> · {user.email}</span>
                            {!user.active ? (
                              <span className="ml-1 text-xs text-amber-700">{t('platform.teams.inactive')}</span>
                            ) : null}
                          </span>
                        </label>
                      </li>
                    ))}
                  </ul>
                )}
              </fieldset>
              <AdminFormActions
                onSave={() => void handleSave()}
                saveLabel={saving ? t('platform.teams.saving') : t('platform.teams.save')}
                saveDisabled={saving}
                saveBusy={saving}
                saveTestId="team-save"
                extra={
                  <button
                    type="button"
                    className="px-3 py-2 text-sm rounded-lg border border-admin-border text-admin-text"
                    onClick={() => {
                      setCreating(false);
                      setSelectedId(null);
                      setDraft(EMPTY_DRAFT);
                    }}
                  >
                    {t('platform.teams.cancel')}
                  </button>
                }
              />
            </div>
          </AdminWidgetCard>
        ) : (
          <p className="text-sm text-admin-muted">{t('platform.teams.selectHint')}</p>
        )}
      </div>
    </div>
  );
};
