import React, { useId, useState } from 'react';
import { sanitizePublicHtml } from '../utils/sanitizeHtml';
import type { IslandProps } from './publicIslandDefinitions';

type BillingPeriod = 'monthly' | 'yearly';

export function PricingTableIsland({
  attrs,
  innerHtml = '',
}: {
  attrs: IslandProps;
  innerHtml?: string;
}): React.ReactElement {
  const toggleId = useId();
  const [billing, setBilling] = useState<BillingPeriod>('monthly');
  const columns = attrs.columns !== '' ? attrs.columns : '3';
  const labelMonthly = attrs.labelMonthly !== '' ? attrs.labelMonthly : 'Monthly';
  const labelYearly = attrs.labelYearly !== '' ? attrs.labelYearly : 'Yearly';
  const safeInner = innerHtml.trim() === '' ? '' : sanitizePublicHtml(innerHtml);

  return (
    <section
      className={`pg-island pg-island--pricing-table pg-pricing pg-pricing-cols-${columns} pg-pricing--billing-toggle`}
      data-island="pricing-table"
      data-billing={billing}
    >
      <div className="pg-pricing-billing-toggle" role="tablist" aria-label={labelMonthly + ' / ' + labelYearly}>
        <button
          type="button"
          role="tab"
          id={`${toggleId}-monthly`}
          aria-selected={billing === 'monthly'}
          className={billing === 'monthly' ? 'pg-pricing-billing-toggle__btn is-active' : 'pg-pricing-billing-toggle__btn'}
          onClick={() => setBilling('monthly')}
        >
          {labelMonthly}
        </button>
        <button
          type="button"
          role="tab"
          id={`${toggleId}-yearly`}
          aria-selected={billing === 'yearly'}
          className={billing === 'yearly' ? 'pg-pricing-billing-toggle__btn is-active' : 'pg-pricing-billing-toggle__btn'}
          onClick={() => setBilling('yearly')}
        >
          {labelYearly}
        </button>
      </div>
      {safeInner !== '' ? (
        <div
          className="pg-pricing__plans"
          data-billing={billing}
          dangerouslySetInnerHTML={{ __html: safeInner }}
        />
      ) : null}
    </section>
  );
}
