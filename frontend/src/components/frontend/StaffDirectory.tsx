import React, { useEffect, useState } from 'react';
import { Mail, MapPin, Phone } from 'lucide-react';
import { fetchPublicStaff, type PublicStaffCard, type PublicStaffLists } from '../../api/staff';
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

const StaffCard: React.FC<{ card: PublicStaffCard }> = ({ card }) => {
  const { t } = useI18n();
  const address = formatAddress(card.address);

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
          <h3 className="font-bold text-theme-text">{card.name}</h3>
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
    </article>
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
