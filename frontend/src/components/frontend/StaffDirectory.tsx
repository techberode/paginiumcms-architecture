import React, { useEffect, useState } from 'react';
import { Mail, MapPin, MessageCircle, Phone } from 'lucide-react';
import {
  fetchPublicStaff,
  fetchStaffCards,
  sendStaffMessage,
  type PublicStaffCard,
  type PublicStaffLists,
} from '../../api/staff';
import { useI18n } from '../../context/I18nContext';
import { PUBLIC_CARD } from '../../theme/publicUiClasses';
import { SocialBrandLink } from '../ui/SocialBrandIcon';

function isSafeStaffHref(url: string): boolean {
  return /^(https?:|mailto:)/i.test(url.trim());
}

function formatAddress(address: PublicStaffCard['address']): string {
  if (!address) {
    return '';
  }
  return [address.street, address.postal, address.city, address.country].filter(Boolean).join(', ');
}

export const StaffCard: React.FC<{ card: PublicStaffCard }> = ({ card }) => {
  const { t } = useI18n();
  const address = formatAddress(card.address);
  const [open, setOpen] = useState(false);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [message, setMessage] = useState('');
  const [busy, setBusy] = useState(false);
  const [sent, setSent] = useState(false);
  const [error, setError] = useState('');

  const submitChat = async (event: React.FormEvent) => {
    event.preventDefault();
    setBusy(true);
    setError('');
    const result = await sendStaffMessage(card.id, { name, email, message });
    setBusy(false);
    if (!result.success) {
      setError(result.error || t('public.staff.chatFailed'));
      return;
    }
    setSent(true);
  };

  return (
    <article className={`${PUBLIC_CARD} p-6`} data-testid={`staff-card-${card.id}`}>
      <div className="flex items-start gap-4">
        {card.avatarUrl ? (
          <img src={card.avatarUrl} alt="" className="w-14 h-14 rounded-2xl object-cover border border-theme-border" />
        ) : (
          <div className="w-14 h-14 rounded-2xl bg-theme-primary/15 text-theme-primary flex items-center justify-center font-bold">
            {card.name.slice(0, 2).toUpperCase()}
          </div>
        )}
        <div className="min-w-0">
          <div className="flex flex-wrap items-center gap-2">
            <h3 className="font-bold text-theme-text">{card.name}</h3>
            {card.chatEnabled ? (
              <span
                className={`text-[11px] font-semibold uppercase tracking-wide ${card.online ? 'text-emerald-600' : 'text-theme-text-muted'}`}
                data-testid={`staff-presence-${card.id}`}
              >
                {card.online ? t('public.staff.online') : t('public.staff.offline')}
              </span>
            ) : null}
          </div>
          {card.jobTitle ? <p className="text-sm text-theme-text-muted">{card.jobTitle}</p> : null}
        </div>
      </div>
      {card.bio ? <p className="mt-4 text-sm leading-relaxed text-theme-text-muted">{card.bio}</p> : null}
      <dl className="mt-4 space-y-2 text-sm">
        {card.email ? (
          <div className="flex items-center gap-2">
            <Mail className="w-4 h-4 shrink-0" />
            <a href={`mailto:${card.email}`} className="hover:underline">
              {card.email}
            </a>
          </div>
        ) : null}
        {card.phone ? (
          <div className="flex items-center gap-2">
            <Phone className="w-4 h-4 shrink-0" />
            <a href={`tel:${card.phone}`}>{card.phone}</a>
          </div>
        ) : null}
        {address ? (
          <div className="flex items-start gap-2">
            <MapPin className="w-4 h-4 shrink-0 mt-0.5" />
            <span>{address}</span>
          </div>
        ) : null}
      </dl>
      {card.experience && card.experience.length > 0 ? (
        <ul className="mt-4 space-y-1 text-sm text-theme-text-muted">
          {card.experience.map((row, index) => (
            <li key={`${card.id}-exp-${index}`}>
              {[row.role, row.org, row.years].filter(Boolean).join(' · ')}
            </li>
          ))}
        </ul>
      ) : null}
      {card.education && card.education.length > 0 ? (
        <ul className="mt-3 space-y-1 text-sm text-theme-text-muted">
          {card.education.map((row, index) => (
            <li key={`${card.id}-edu-${index}`}>
              {[row.field, row.school, row.years].filter(Boolean).join(' · ')}
            </li>
          ))}
        </ul>
      ) : null}
      {card.socials && card.socials.length > 0 ? (
        <nav className="mt-4 flex flex-wrap gap-2" aria-label={t('public.staff.chat')}>
          {card.socials
            .filter((social) => isSafeStaffHref(social.url))
            .map((social) => (
              <SocialBrandLink
                key={`${card.id}-${social.platform}-${social.url}`}
                platform={social.platform}
                href={social.url}
                target="_blank"
                rel="noopener noreferrer"
                title={social.label || (social.directChat ? t('public.staff.chat') : social.platform)}
              />
            ))}
        </nav>
      ) : null}
      {card.chatEnabled ? (
        <div className="mt-4">
          <button
            type="button"
            className="inline-flex items-center gap-2 text-sm font-semibold text-theme-primary"
            data-testid={`staff-chat-toggle-${card.id}`}
            onClick={() => setOpen((current) => !current)}
          >
            <MessageCircle className="w-4 h-4" />
            {card.online ? t('public.staff.liveChat') : t('public.staff.leaveMessage')}
          </button>
          {open ? (
            sent ? (
              <p className="mt-3 text-sm text-theme-text-muted">{t('public.staff.chatSent')}</p>
            ) : (
              <form className="mt-3 space-y-2" onSubmit={(event) => void submitChat(event)}>
                <input
                  required
                  className="w-full rounded-lg border border-theme-border bg-theme-canvas px-3 py-2 text-sm"
                  placeholder={t('public.contact.fields.name')}
                  value={name}
                  onChange={(event) => setName(event.target.value)}
                />
                <input
                  required
                  type="email"
                  className="w-full rounded-lg border border-theme-border bg-theme-canvas px-3 py-2 text-sm"
                  placeholder={t('public.contact.fields.email')}
                  value={email}
                  onChange={(event) => setEmail(event.target.value)}
                />
                <textarea
                  required
                  minLength={10}
                  rows={3}
                  className="w-full rounded-lg border border-theme-border bg-theme-canvas px-3 py-2 text-sm"
                  placeholder={t('public.contact.fields.messagePlaceholder')}
                  value={message}
                  onChange={(event) => setMessage(event.target.value)}
                />
                {error ? <p className="text-sm text-red-600">{error}</p> : null}
                <button type="submit" className="btn btn-primary text-sm" disabled={busy}>
                  {busy ? t('public.staff.sending') : t('public.staff.send')}
                </button>
              </form>
            )
          ) : null}
        </div>
      ) : null}
    </article>
  );
};

export const StaffCardsSection: React.FC<{
  mode?: string;
  user?: string;
  type?: string;
  team?: string;
}> = ({ mode, user, type, team }) => {
  const [cards, setCards] = useState<PublicStaffCard[]>([]);

  useEffect(() => {
    void fetchStaffCards({
      user: mode === 'user' ? user : undefined,
      type: mode === 'type' ? type : undefined,
      team: mode === 'team' ? team : undefined,
    }).then(setCards);
  }, [mode, user, type, team]);

  if (cards.length === 0) {
    return null;
  }

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4" data-testid="staff-cards-island">
      {cards.map((card) => (
        <StaffCard key={card.id} card={card} />
      ))}
    </div>
  );
};

export const StaffDirectory: React.FC = () => {
  const { t } = useI18n();
  const [lists, setLists] = useState<PublicStaffLists>({ contacts: [], support: [] });

  useEffect(() => {
    void fetchPublicStaff().then(setLists);
  }, []);

  if (lists.contacts.length === 0 && lists.support.length === 0) {
    return null;
  }

  return (
    <div className="space-y-10" data-testid="staff-directory">
      {lists.contacts.length > 0 ? (
        <section>
          <h2 className="text-xl font-black mb-4">{t('public.staff.contactsTitle')}</h2>
          <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            {lists.contacts.map((card) => (
              <StaffCard key={card.id} card={card} />
            ))}
          </div>
        </section>
      ) : null}
      {lists.support.length > 0 ? (
        <section>
          <h2 className="text-xl font-black mb-4">{t('public.staff.supportTitle')}</h2>
          <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            {lists.support.map((card) => (
              <StaffCard key={`support-${card.id}`} card={card} />
            ))}
          </div>
        </section>
      ) : null}
    </div>
  );
};

export default StaffDirectory;
