import React from 'react';
import type { LucideIcon } from 'lucide-react';
import { ADMIN_OFFER_CARD, ADMIN_OFFER_CARD_ACTIVE } from '../../theme/adminUiClasses';

export interface AdminOfferCardProps {
  title: string;
  subtitle?: string;
  icon?: LucideIcon;
  active?: boolean;
  disabled?: boolean;
  onSelect?: () => void;
  action?: React.ReactNode;
  testId?: string;
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
}) => (
  <div className={`${active ? ADMIN_OFFER_CARD_ACTIVE : ADMIN_OFFER_CARD} p-4`}>
    <div className="flex items-start gap-3">
      {Icon ? (
        <div
          className={`rounded-lg p-2 shrink-0 ${
            active
              ? 'bg-admin-primary/15 text-admin-primary'
              : 'bg-admin-canvas text-admin-primary'
          }`}
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
        <p className="font-semibold text-sm text-admin-text truncate">{title}</p>
        {subtitle ? <p className="text-xs text-admin-muted mt-1 leading-relaxed">{subtitle}</p> : null}
      </button>
      {action ? <div className="shrink-0">{action}</div> : null}
    </div>
  </div>
);

export default AdminOfferCard;
