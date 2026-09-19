import React, { useEffect, useMemo, useState } from 'react';
import { messagesApi, type MessageRoute } from '../../api/messages';
import { teamsApi, type Team, type TeamMember } from '../../api/teams';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { AdminWidgetCard } from '../ui/AdminWidgetCard';
import { ADMIN_INPUT } from '../../theme/adminUiClasses';
import { filterPeople, groupPeopleByTeam } from '../../utils/contactRoutingPeople';

function parseSubjects(raw: string): string[] {
  return raw
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter((line) => line !== '');
}

function emptyRoute(subject: string): MessageRoute {
  return { subject, enabled: false, teamIds: [], userIds: [] };
}

export const ContactRoutingPanel: React.FC<{ subjects: string }> = ({ subjects }) => {
  const { t } = useI18n();
  const toast = useToast();
  const [enabled, setEnabled] = useState(false);
  const [routes, setRoutes] = useState<MessageRoute[]>([]);
  const [teams, setTeams] = useState<Team[]>([]);
  const [users, setUsers] = useState<TeamMember[]>([]);
  const [saving, setSaving] = useState(false);

  const subjectList = useMemo(() => parseSubjects(subjects), [subjects]);

  useEffect(() => {
    void Promise.all([messagesApi.routing(), teamsApi.list()]).then(([routing, index]) => {
      setEnabled(routing.enabled);
      setTeams(index.teams);
      setUsers(index.users);
      const bySubject = new Map(routing.routes.map((route) => [route.subject, route]));
      setRoutes(subjectList.map((subject) => bySubject.get(subject) ?? emptyRoute(subject)));
    });
  }, [subjectList]);

  const updateRoute = (subject: string, patch: Partial<MessageRoute>) => {
    setRoutes((current) =>
      current.map((route) => (route.subject === subject ? { ...route, ...patch } : route))
    );
  };

  const toggleId = (list: string[], id: string): string[] =>
    list.includes(id) ? list.filter((item) => item !== id) : [...list, id];

  const save = async () => {
    setSaving(true);
    const saved = await messagesApi.saveRouting({ enabled, routes });
    setSaving(false);
    if (!saved) {
      toast.error(t('messages.routing.saveFailed'));
      return;
    }
    toast.success(t('messages.routing.saved'));
  };

  return (
    <AdminWidgetCard title={t('messages.routing.title')}>
      <p className="text-xs text-admin-muted mb-3">{t('messages.routing.hint')}</p>
      <label className="flex items-start gap-2 text-sm text-admin-text mb-4">
        <input
          type="checkbox"
          className="mt-0.5"
          checked={enabled}
          data-testid="contact-routing-enabled"
          onChange={(event) => setEnabled(event.target.checked)}
        />
        <span>{t('messages.routing.enabled')}</span>
      </label>
      <div className="space-y-3">
        {routes.map((route) => (
          <div
            key={route.subject}
            className="rounded-lg border border-admin-border bg-admin-canvas p-3 space-y-2"
            data-testid={`contact-route-${route.subject}`}
          >
            <label className="flex items-center gap-2 text-sm font-semibold text-admin-text">
              <input
                type="checkbox"
                checked={route.enabled}
                onChange={(event) => updateRoute(route.subject, { enabled: event.target.checked })}
              />
              {route.subject}
            </label>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
              <fieldset>
                <legend className="text-xs text-admin-muted mb-1">{t('messages.routing.teams')}</legend>
                {teams.map((team) => (
                  <label key={team.id} className="flex items-center gap-2 py-0.5">
                    <input
                      type="checkbox"
                      checked={route.teamIds.includes(team.id)}
                      onChange={() =>
                        updateRoute(route.subject, { teamIds: toggleId(route.teamIds, team.id) })
                      }
                    />
                    <span className="flex items-center gap-1.5 min-w-0">
                      <span className="truncate">{team.name}</span>
                      {team.type === 'external' ? (
                        <span className="mail-tag mail-tag-3 shrink-0">{t('platform.teams.externalBadge')}</span>
                      ) : null}
                    </span>
                  </label>
                ))}
              </fieldset>
              <PeoplePicker
                users={users}
                teams={teams}
                selectedIds={route.userIds}
                onToggle={(userId) => updateRoute(route.subject, { userIds: toggleId(route.userIds, userId) })}
              />
            </div>
          </div>
        ))}
      </div>
      <button type="button" className="btn btn-primary mt-4 text-sm" disabled={saving} onClick={() => void save()}>
        {saving ? t('messages.routing.saving') : t('messages.routing.save')}
      </button>
    </AdminWidgetCard>
  );
};

const PeoplePicker: React.FC<{
  users: TeamMember[];
  teams: Team[];
  selectedIds: string[];
  onToggle: (userId: string) => void;
}> = ({ users, teams, selectedIds, onToggle }) => {
  const { t } = useI18n();
  const [query, setQuery] = useState('');
  const visible = useMemo(() => filterPeople(users, query), [users, query]);
  const groups = useMemo(() => groupPeopleByTeam(visible, teams), [visible, teams]);
  const selected = users.filter((user) => selectedIds.includes(user.id));
  const searching = query.trim() !== '';

  return (
    <fieldset>
      <legend className="text-xs text-admin-muted mb-1">
        {t('messages.routing.users')}
        {selected.length > 0 ? ` · ${t('messages.routing.peopleSelected', { count: String(selected.length) })}` : ''}
      </legend>
      {selected.length > 0 ? (
        <div className="flex flex-wrap gap-1 mb-2">
          {selected.map((user) => (
            <button
              key={user.id}
              type="button"
              className="mail-tag mail-tag-1"
              onClick={() => onToggle(user.id)}
              title={user.email}
            >
              {(user.name || user.email) + ' ×'}
            </button>
          ))}
        </div>
      ) : null}
      <input
        type="search"
        data-testid="contact-routing-people-search"
        value={query}
        onChange={(event) => setQuery(event.target.value)}
        placeholder={t('messages.routing.peopleSearch')}
        className={`${ADMIN_INPUT} mb-2 text-sm`}
      />
      <div className="space-y-1">
        {groups.map((group) => {
          const label = group.id === 'other' ? t('messages.routing.peopleOther') : group.label;
          const picked = group.users.filter((user) => selectedIds.includes(user.id)).length;
          return (
            <details
              key={group.id}
              className="rounded-lg border border-admin-border bg-admin-card"
              open={searching || picked > 0}
              data-testid={`contact-routing-people-${group.id}`}
            >
              <summary className="cursor-pointer px-2 py-1.5 text-xs font-semibold text-admin-text flex items-center gap-1.5">
                <span className="truncate">{label}</span>
                {group.external ? <span className="mail-tag mail-tag-3">{t('platform.teams.externalBadge')}</span> : null}
                <span className="ml-auto text-admin-muted font-normal">
                  {picked > 0 ? `${picked}/` : ''}
                  {group.users.length}
                </span>
              </summary>
              <div className="max-h-40 overflow-y-auto border-t border-admin-border px-2 py-1">
                {group.users.map((user) => (
                  <label key={user.id} className="flex items-center gap-2 py-0.5">
                    <input
                      type="checkbox"
                      checked={selectedIds.includes(user.id)}
                      onChange={() => onToggle(user.id)}
                    />
                    <span className="truncate" title={user.email}>
                      {user.name || user.email}
                    </span>
                  </label>
                ))}
              </div>
            </details>
          );
        })}
      </div>
    </fieldset>
  );
};
