<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout\Services;

/**
 * Pricing table wrapper (It.58f-i-e — optional monthly/yearly billing toggle island).
 */
final class PricingTableRenderer
{
    /** @var list<string> */
    private const COLUMNS = ['2', '3'];

    /** @var list<string> */
    private const BILLING_TOGGLES = ['monthly-yearly'];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function attributeSchema(): array
    {
        return [
            'columns' => ['type' => 'enum', 'options' => ['', '2', '3']],
            'billing-toggle' => ['type' => 'enum', 'options' => ['', 'monthly-yearly']],
            'label-monthly' => ['type' => 'string'],
            'label-yearly' => ['type' => 'string'],
        ];
    }

    /**
     * @param array<string, string> $attrs
     */
    public static function render(array $attrs, string $innerHtml): string
    {
        $columns = self::enum($attrs['columns'] ?? '', self::COLUMNS, '3');
        $billing = self::enum($attrs['billing-toggle'] ?? '', self::BILLING_TOGGLES, '');
        $labelMonthlyRaw = trim($attrs['label-monthly'] ?? '');
        $labelMonthly = $labelMonthlyRaw !== '' ? $labelMonthlyRaw : 'Monthly';
        $labelYearlyRaw = trim($attrs['label-yearly'] ?? '');
        $labelYearly = $labelYearlyRaw !== '' ? $labelYearlyRaw : 'Yearly';

        if ($billing !== 'monthly-yearly') {
            return '<div class="pg-pricing pg-pricing-cols-' . self::text($columns) . '">' . $innerHtml . '</div>';
        }

        $html = '<section class="pg-island pg-island--pricing-table pg-pricing pg-pricing-cols-'
            . self::text($columns) . ' pg-pricing--billing-toggle"';
        $html .= ' data-island="pricing-table"';
        $html .= ' data-billing-toggle="monthly-yearly"';
        $html .= ' data-columns="' . self::text($columns) . '"';
        $html .= ' data-label-monthly="' . self::text($labelMonthly) . '"';
        $html .= ' data-label-yearly="' . self::text($labelYearly) . '"';
        $html .= '>';
        $html .= $innerHtml;
        $html .= '</section>';

        return $html;
    }

    /**
     * @param list<string> $allowed
     */
    private static function enum(string $raw, array $allowed, string $default): string
    {
        $value = strtolower(trim($raw));
        if ($value === '') {
            return $default;
        }

        return in_array($value, $allowed, true) ? $value : $default;
    }

    private static function text(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
