import React from 'react';
import type { LucideIcon } from 'lucide-react';
import { ADMIN_OFFER_CARD, ADMIN_OFFER_CARD_ACTIVE } from '../../theme/adminUiClasses';

export interface AdminOfferCardAvatar {
  name: string;
  src?: string;
}

export interface AdminOfferCardProps {
  title: string;
  subtitle?: string;
  icon?: LucideIcon;
  active?: boolean;
  disabled?: boolean;
  onSelect?: () => void;
  action?: React.ReactNode;
  testId?: string;
  accentColor?: string;
  avatars?: AdminOfferCardAvatar[];
  badge?: string;
}

export const AdminOfferCard: React.FC<AdminOfferCardProps> = ({
  title,
  subtitle,
  icon: Icon,
  active = false,
  disabled = false,
  onSelect,
  action,
  testId,
  accentColor,
  avatars = [],
  badge,
}) => (
  <div
    className={`${active ? ADMIN_OFFER_CARD_ACTIVE : ADMIN_OFFER_CARD} p-4 overflow-hidden`}
    style={accentColor ? { borderColor: accentColor, boxShadow: `inset 4px 0 0 ${accentColor}` } : undefined}
  >
    <div className="flex items-start gap-3">
      {Icon ? (
        <div
          className={`rounded-lg p-2 shrink-0 ${
            active
              ? 'bg-admin-primary/15 text-admin-primary'
              : 'bg-admin-canvas text-admin-primary'
          }`}
          style={accentColor ? { backgroundColor: `${accentColor}22`, color: accentColor } : undefined}
        >
          <Icon className="h-4 w-4" aria-hidden />
        </div>
      ) : null}
      <button
        type="button"
        disabled={disabled || !onSelect}
        onClick={onSelect}
        data-testid={testId}
        className="flex-1 min-w-0 text-left disabled:cursor-default cursor-pointer"
      >
        <p className="font-semibold text-sm text-admin-text flex items-center gap-1.5 min-w-0">
          <span className="truncate">{title}</span>
          {badge ? <span className="mail-tag mail-tag-3 shrink-0">{badge}</span> : null}
        </p>
        {subtitle ? <p className="text-xs text-admin-muted mt-1 leading-relaxed">{subtitle}</p> : null}
        {avatars.length > 0 ? (
          <div className="mt-2 flex -space-x-2" aria-hidden>
            {avatars.slice(0, 5).map((avatar) =>
              avatar.src ? (
                <img
                  key={avatar.name}
                  src={avatar.src}
                  alt=""
                  className="h-7 w-7 rounded-full border-2 border-admin-card object-cover"
                />
              ) : (
                <span
                  key={avatar.name}
                  className="inline-flex h-7 w-7 items-center justify-center rounded-full border-2 border-admin-card bg-admin-canvas text-[10px] font-semibold text-admin-text"
                >
                  {avatar.name.slice(0, 1).toUpperCase()}
                </span>
              )
            )}
          </div>
        ) : null}
      </button>
      {action ? <div className="shrink-0">{action}</div> : null}
    </div>
  </div>
);

export default AdminOfferCard;
