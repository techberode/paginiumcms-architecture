import React, { useCallback, useEffect, useState } from 'react';
import { registrationInvitesApi, type IssuedRegistrationInvite, type RegistrationInvite } from '../../api/registrationInvites';
import { teamsApi, type Team } from '../../api/teams';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { AdminWidgetCard } from '../ui/AdminWidgetCard';
import { ADMIN_INPUT } from '../../theme/adminUiClasses';

export const RegistrationInvitesPanel: React.FC<{ defaultTeamId?: string }> = ({ defaultTeamId = '' }) => {
  const { t } = useI18n();
  const toast = useToast();
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);
  const [email, setEmail] = useState('');
  const [teamId, setTeamId] = useState(defaultTeamId);
  const [sendMail, setSendMail] = useState(true);
  const [teams, setTeams] = useState<Team[]>([]);
  const [invites, setInvites] = useState<RegistrationInvite[]>([]);
  const [issued, setIssued] = useState<IssuedRegistrationInvite | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const [list, index] = await Promise.all([registrationInvitesApi.list(), teamsApi.list()]);
      setInvites(list);
      setTeams(index.teams);
    } catch {
      toast.error(t('platform.registrationInvites.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [t, toast]);

  useEffect(() => {
    void load();
  }, [load]);

  useEffect(() => {
    if (defaultTeamId) {
      setTeamId(defaultTeamId);
    }
  }, [defaultTeamId]);

  const handleIssue = async () => {
    if (email.trim() === '') {
      toast.error(t('platform.registrationInvites.toast.emailRequired'));
      return;
    }
    setSending(true);
    try {
      const response = await registrationInvitesApi.create({
        email: email.trim(),
        teamId: teamId || undefined,
        sendMail,
        source: 'admin',
      });
      if (!response.success || !response.data) {
        toast.error(response.error || t('platform.registrationInvites.toast.sendFailed'));
        return;
      }
      setIssued(response.data);
      setEmail('');
      toast.success(
        response.data.mailed
          ? t('platform.registrationInvites.toast.sent')
          : t('platform.registrationInvites.toast.created')
      );
      await load();
    } finally {
      setSending(false);
    }
  };

  const copyUrl = async (url: string) => {
    try {
      await navigator.clipboard.writeText(url);
      toast.success(t('platform.registrationInvites.toast.copied'));
    } catch {
      toast.error(t('platform.registrationInvites.toast.copyFailed'));
    }
  };

  return (
    <AdminWidgetCard title={t('platform.registrationInvites.title')}>
      <div className="space-y-4" data-testid="registration-invites">
        <p className="text-sm text-admin-muted">{t('platform.registrationInvites.hint')}</p>
        <label className="block text-sm">
          <span className="text-admin-muted">{t('platform.registrationInvites.email')}</span>
          <input
            data-testid="registration-invite-email"
            type="email"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            className={`mt-1 ${ADMIN_INPUT}`}
          />
        </label>
        <label className="block text-sm">
          <span className="text-admin-muted">{t('platform.registrationInvites.teamHint')}</span>
          <select
            data-testid="registration-invite-team"
            value={teamId}
            onChange={(event) => setTeamId(event.target.value)}
            className={`mt-1 ${ADMIN_INPUT}`}
          >
            <option value="">{t('platform.registrationInvites.teamNone')}</option>
            {teams.map((team) => (
              <option key={team.id} value={team.id}>
                {team.name}
              </option>
            ))}
          </select>
        </label>
        <label className="flex items-center gap-2 text-sm text-admin-text">
          <input
            type="checkbox"
            checked={sendMail}
            onChange={(event) => setSendMail(event.target.checked)}
          />
          {t('platform.registrationInvites.sendMail')}
        </label>
        <button
          type="button"
          data-testid="registration-invite-send"
          disabled={sending}
          onClick={() => void handleIssue()}
          className="inline-flex items-center px-3 py-2 rounded-xl bg-admin-primary text-white text-sm disabled:opacity-60"
        >
          {sending ? t('platform.registrationInvites.sending') : t('platform.registrationInvites.send')}
        </button>
        {issued ? (
          <p className="text-xs break-all text-admin-muted">
            {t('platform.registrationInvites.linkOnce')}{' '}
            <button type="button" className="text-admin-primary underline" onClick={() => void copyUrl(issued.url)}>
              {t('platform.registrationInvites.copy')}
            </button>
          </p>
        ) : null}
        {loading ? (
          <p className="text-sm text-admin-muted">{t('platform.registrationInvites.loading')}</p>
        ) : invites.length === 0 ? (
          <p className="text-sm text-admin-muted">{t('platform.registrationInvites.empty')}</p>
        ) : (
          <ul className="space-y-2">
            {invites.map((invite) => (
              <li
                key={invite.id}
                className="flex items-center justify-between gap-2 rounded-lg border border-admin-border px-3 py-2 text-sm"
              >
                <span>
                  {invite.email}
                  <span className="block text-xs text-admin-muted">
                    {invite.usedAt > 0
                      ? t('platform.registrationInvites.used')
                      : t('platform.registrationInvites.pending')}
                  </span>
                </span>
                {invite.usedAt === 0 ? (
                  <button
                    type="button"
                    className="text-xs text-rose-600"
                    onClick={() => {
                      void registrationInvitesApi.revoke(invite.id).then((ok) => {
                        if (ok) {
                          void load();
                        }
                      });
                    }}
                  >
                    {t('platform.registrationInvites.revoke')}
                  </button>
                ) : null}
              </li>
            ))}
          </ul>
        )}
      </div>
    </AdminWidgetCard>
  );
};

export default RegistrationInvitesPanel;
