<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout\Services;

/**
 * Pricing plan card (It.58f-i-e — dual monthly/yearly amounts for billing toggle).
 */
final class PricingPlanRenderer
{
    /** @var list<string> */
    private const VARIANTS = ['default', 'featured'];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function attributeSchema(): array
    {
        return [
            'name' => ['type' => 'string'],
            'price' => ['type' => 'string'],
            'period' => ['type' => 'string'],
            'price-monthly' => ['type' => 'string'],
            'price-yearly' => ['type' => 'string'],
            'period-monthly' => ['type' => 'string'],
            'period-yearly' => ['type' => 'string'],
            'cta' => ['type' => 'string'],
            'href' => ['type' => 'string'],
            'variant' => ['type' => 'enum', 'options' => ['', 'default', 'featured']],
        ];
    }

    /**
     * @param array<string, string> $attrs
     */
    public static function render(array $attrs, string $innerHtml): string
    {
        $name = self::text($attrs['name'] ?? '');
        $cta = self::text($attrs['cta'] ?? '');
        $href = self::text($attrs['href'] ?? '#');
        $variant = self::enum($attrs['variant'] ?? '', self::VARIANTS, 'default');

        $priceMonthlyRaw = trim($attrs['price-monthly'] ?? '');
        $monthlyAmount = $priceMonthlyRaw !== '' ? $priceMonthlyRaw : trim($attrs['price'] ?? '');
        $yearlyAmount = trim($attrs['price-yearly'] ?? '');
        $periodMonthlyRaw = trim($attrs['period-monthly'] ?? '');
        $periodMonthly = $periodMonthlyRaw !== '' ? $periodMonthlyRaw : trim($attrs['period'] ?? '');
        $periodYearly = trim($attrs['period-yearly'] ?? '');

        $dualBilling = $yearlyAmount !== '';

        $html = '<article class="pg-plan pg-plan-' . self::text($variant) . '">';
        if ($name !== '') {
            $html .= '<h3 class="pg-plan-name">' . self::text($name) . '</h3>';
        }
        $html .= '<p class="pg-plan-price">';
        if ($dualBilling) {
            $html .= '<span class="pg-plan-amount pg-plan-amount--monthly">' . self::text($monthlyAmount) . '</span>';
            $html .= '<span class="pg-plan-amount pg-plan-amount--yearly">' . self::text($yearlyAmount) . '</span>';
            if ($periodMonthly !== '') {
                $html .= '<span class="pg-plan-period pg-plan-period--monthly">' . self::text($periodMonthly) . '</span>';
            }
            if ($periodYearly !== '') {
                $html .= '<span class="pg-plan-period pg-plan-period--yearly">' . self::text($periodYearly) . '</span>';
            }
        } else {
            $html .= '<span class="pg-plan-amount">' . self::text($monthlyAmount) . '</span>';
            if ($periodMonthly !== '') {
                $html .= '<span class="pg-plan-period">' . self::text($periodMonthly) . '</span>';
            }
        }
        $html .= '</p>';
        $html .= '<ul class="pg-plan-list">' . $innerHtml . '</ul>';
        if ($cta !== '') {
            $html .= '<a class="pg-btn pg-btn-primary pg-plan-cta" href="' . $href . '">' . self::text($cta) . '</a>';
        }
        $html .= '</article>';

        return $html;
    }

    /**
     * @param list<string> $allowed
     */
    private static function enum(string $raw, array $allowed, string $default): string
    {
        $value = strtolower(trim($raw));

        return $value !== '' && in_array($value, $allowed, true) ? $value : $default;
    }

    private static function text(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
