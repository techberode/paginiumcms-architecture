import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { BookOpen, Code2, Headphones, Plus, RefreshCw, Trash2, Users2, Wrench } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { TEAM_COLOR_SWATCHES, teamsApi, type Team, type TeamMember, type TeamType } from '../../api/teams';
import { resolveUserAvatarUrl } from '../../api/users';
import { RegistrationInvitesPanel } from './RegistrationInvitesPanel';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
import { useAdminConfirm } from '../../hooks/useAdminConfirm';
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
  external: Code2,
  custom: Users2,
};

const TYPE_TABS: TeamType[] = ['editorial', 'support', 'ops', 'external', 'custom'];

const EMPTY_DRAFT = {
  name: '',
  type: 'editorial' as TeamType,
  memberUserIds: [] as string[],
  color: '',
  chatEnabled: false,
  replyMailEnabled: false,
  replyMail: '',
};

const namedTypes: TeamType[] = ['custom', 'external'];

export const TeamsManager: React.FC = () => {
  const { t } = useI18n();
  const confirmDestructive = useAdminConfirm();
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
      trimmed === 'External' ||
      trimmed === t('platform.teams.types.editorial') ||
      trimmed === t('platform.teams.types.support') ||
      trimmed === t('platform.teams.types.ops') ||
      trimmed === t('platform.teams.types.external')
    );
  };

  const changeType = (next: TeamType) => {
    setDraft((current) => ({
      ...current,
      type: next,
      name: namedTypes.includes(next) && isPresetName(current.name) ? '' : current.name,
      chatEnabled: creating ? next === 'support' : current.chatEnabled,
    }));
  };

  const openTeam = (team: Team) => {
    setCreating(false);
    setSelectedId(team.id);
    setDraft({
      name: namedTypes.includes(team.type) ? team.name : '',
      type: team.type,
      memberUserIds: [...team.memberUserIds],
      color: team.color ?? '',
      chatEnabled: team.chatEnabled ?? team.type === 'support',
      replyMailEnabled: Boolean(team.replyMailEnabled),
      replyMail: team.replyMail ?? '',
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
    if (namedTypes.includes(draft.type) && !draft.name.trim()) {
      toast.error(t('platform.teams.toast.nameRequired'));
      return;
    }
    if (draft.replyMailEnabled && !draft.replyMail.trim()) {
      toast.error(t('platform.teams.toast.replyMailRequired'));
      return;
    }

    setSaving(true);
    try {
      const payload = {
        name: namedTypes.includes(draft.type) ? draft.name.trim() : '',
        type: draft.type,
        memberUserIds: draft.memberUserIds,
        color: draft.color,
        chatEnabled: draft.chatEnabled,
        replyMailEnabled: draft.replyMailEnabled,
        replyMail: draft.replyMail.trim().toLowerCase(),
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
    if (!(await confirmDestructive(t('platform.teams.confirmDelete', { name: teamTitle(team) })))) {
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
  const teamTitle = (team: Team) =>
    namedTypes.includes(team.type) ? team.name || typeLabel(team.type) : typeLabel(team.type);
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
          ...TYPE_TABS.map((type) => ({
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
                badge={team.type === 'external' ? t('platform.teams.externalBadge') : undefined}
                subtitle={`${namedTypes.includes(team.type) && team.type !== 'external' ? `${typeLabel(team.type)} · ` : ''}${t('platform.teams.memberCount', { count: String(team.memberUserIds.length) })}`}
                accentColor={team.color}
                avatars={team.members.map((member) => ({
                  name: member.name,
                  src: resolveUserAvatarUrl(member.avatarUrl) || undefined,
                }))}
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
          <div className="space-y-4">
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
                  {TYPE_TABS.map((type) => (
                    <option key={type} value={type}>
                      {typeLabel(type)}
                    </option>
                  ))}
                </select>
              </label>
              {namedTypes.includes(draft.type) ? (
                <label className="block text-sm">
                  <span className="text-admin-muted">{t('platform.teams.name')}</span>
                  <input
                    data-testid="team-name"
                    value={draft.name}
                    onChange={(event) => setDraft((current) => ({ ...current, name: event.target.value }))}
                    placeholder={
                      draft.type === 'external'
                        ? t('platform.teams.externalNamePlaceholder')
                        : t('platform.teams.namePlaceholder')
                    }
                    className={`mt-1 ${ADMIN_INPUT}`}
                  />
                  <span className="mt-1 block text-xs text-admin-muted">
                    {draft.type === 'external' ? t('platform.teams.externalNameHint') : t('platform.teams.nameHint')}
                  </span>
                  {draft.type === 'external' ? (
                    <span className="mail-tag mail-tag-3 mt-2 inline-flex">{t('platform.teams.externalBadge')}</span>
                  ) : null}
                </label>
              ) : null}
              <fieldset>
                <legend className="text-sm text-admin-muted mb-2">{t('platform.teams.color')}</legend>
                <div className="flex flex-wrap items-center gap-2">
                  {TEAM_COLOR_SWATCHES.map((swatch) => (
                    <button
                      key={swatch}
                      type="button"
                      data-testid={`team-color-${swatch}`}
                      aria-label={swatch}
                      className={`h-7 w-7 rounded-full border ${
                        draft.color === swatch ? 'ring-2 ring-admin-primary border-white' : 'border-admin-border'
                      }`}
                      style={{ backgroundColor: swatch }}
                      onClick={() => setDraft((current) => ({ ...current, color: swatch }))}
                    />
                  ))}
                  <input
                    type="color"
                    data-testid="team-color-custom"
                    value={draft.color || '#2563eb'}
                    onChange={(event) => setDraft((current) => ({ ...current, color: event.target.value }))}
                    className="h-7 w-10 rounded border border-admin-border bg-transparent"
                  />
                  {draft.color ? (
                    <button
                      type="button"
                      className="text-xs text-admin-muted underline"
                      onClick={() => setDraft((current) => ({ ...current, color: '' }))}
                    >
                      {t('platform.teams.colorClear')}
                    </button>
                  ) : null}
                </div>
              </fieldset>
              {draft.type === 'external' ? (
                <p className="text-sm text-admin-muted">
                  {t('platform.teams.externalHint')}{' '}
                  <a href="/team-chat" className="text-admin-primary hover:underline">
                    {t('platform.teams.openTeamChat')}
                  </a>
                </p>
              ) : (
              <label className="flex items-start gap-2 rounded-lg border border-admin-border bg-admin-canvas px-3 py-2.5 text-sm text-admin-text">
                <input
                  type="checkbox"
                  className="mt-0.5"
                  data-testid="team-chat-enabled"
                  checked={draft.chatEnabled}
                  onChange={(event) => setDraft((current) => ({ ...current, chatEnabled: event.target.checked }))}
                />
                <span>
                  {t('platform.teams.chatEnabled')}
                  <span className="block text-xs text-admin-muted">{t('platform.teams.chatHint')}</span>
                </span>
              </label>
              )}
              <label className="flex items-start gap-2 rounded-lg border border-admin-border bg-admin-canvas px-3 py-2.5 text-sm text-admin-text">
                <input
                  type="checkbox"
                  className="mt-0.5"
                  data-testid="team-reply-mail-enabled"
                  checked={draft.replyMailEnabled}
                  onChange={(event) => setDraft((current) => ({ ...current, replyMailEnabled: event.target.checked }))}
                />
                <span>
                  {t('platform.teams.replyMailEnabled')}
                  <span className="block text-xs text-admin-muted">{t('platform.teams.replyMailHint')}</span>
                </span>
              </label>
              {draft.replyMailEnabled ? (
                <label className="block text-sm">
                  <span className="text-admin-muted">{t('platform.teams.replyMail')}</span>
                  <input
                    data-testid="team-reply-mail"
                    type="email"
                    value={draft.replyMail}
                    onChange={(event) => setDraft((current) => ({ ...current, replyMail: event.target.value }))}
                    placeholder={t('platform.teams.replyMailPlaceholder')}
                    className={`mt-1 ${ADMIN_INPUT}`}
                  />
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
          {!creating && selected && draft.type === 'external' ? (
            <RegistrationInvitesPanel defaultTeamId={selected.id} />
          ) : null}
          </div>
        ) : (
          <p className="text-sm text-admin-muted">{t('platform.teams.selectHint')}</p>
        )}
      </div>
    </div>
  );
};
