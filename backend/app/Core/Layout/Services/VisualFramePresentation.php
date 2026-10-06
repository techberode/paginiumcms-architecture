<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout\Services;

/**
 * Optional presentation wrapper for shortcodes/widgets inserted via visual modals.
 */
final class VisualFramePresentation
{
    public const MAX_WIDTH_MIN = 280;

    public const MAX_WIDTH_MAX = 1280;

    /** @var list<string> */
    public const ALIGNMENTS = ['left', 'center', 'right'];

    /** @var list<string> */
    public const TEXT_SIZES = ['sm', 'md', 'lg', 'xl'];

    /** @var list<string> */
    public const TONES = ['default', 'muted', 'primary', 'danger'];

    /** @var list<string> */
    public const MARKS = ['none', 'soft', 'strong'];

    /** @var list<string> */
    public const MOTIONS = ['none', 'fade-up', 'stagger'];

    /** @var list<string> */
    public const MOTION_DELAYS = ['normal', 'short'];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function attributeSchema(): array
    {
        return [
            'align' => ['type' => 'enum', 'options' => self::ALIGNMENTS],
            'max-width' => ['type' => 'int'],
            'text-size' => ['type' => 'enum', 'options' => self::TEXT_SIZES],
            'tone' => ['type' => 'enum', 'options' => self::TONES],
            'mark' => ['type' => 'enum', 'options' => self::MARKS],
            'bold' => ['type' => 'bool'],
            'italic' => ['type' => 'bool'],
            'underline' => ['type' => 'bool'],
            'motion' => ['type' => 'enum', 'options' => self::MOTIONS],
            'motion-delay' => ['type' => 'enum', 'options' => self::MOTION_DELAYS],
        ];
    }

    /**
     * @param array<string, string> $attrs
     */
    public static function render(array $attrs, string $innerHtml): string
    {
        $align = self::normalizeAlign($attrs['align'] ?? 'center');
        $maxWidth = self::normalizeMaxWidth($attrs['max-width'] ?? '');
        $textSize = self::normalizeTextSize($attrs['text-size'] ?? 'md');
        $tone = self::normalizeTone($attrs['tone'] ?? 'default');
        $mark = self::normalizeMark($attrs['mark'] ?? 'none');
        $bold = self::isTruthy($attrs['bold'] ?? 'false');
        $italic = self::isTruthy($attrs['italic'] ?? 'false');
        $underline = self::isTruthy($attrs['underline'] ?? 'false');

        $classes = ['pg-visual-frame', 'pg-visual-frame--align-' . $align];
        if ($textSize !== 'md') {
            $classes[] = 'pg-visual-frame--text-' . $textSize;
        }
        if ($tone !== 'default') {
            $classes[] = 'pg-visual-frame--tone-' . $tone;
        }
        if ($mark !== 'none') {
            $classes[] = 'pg-visual-frame--mark-' . $mark;
        }
        if ($bold) {
            $classes[] = 'pg-visual-frame--bold';
        }
        if ($italic) {
            $classes[] = 'pg-visual-frame--italic';
        }
        if ($underline) {
            $classes[] = 'pg-visual-frame--underline';
        }
        if ($maxWidth !== null) {
            $classes[] = 'pg-visual-frame--max-' . $maxWidth;
        }

        $motion = self::normalizeMotion($attrs['motion'] ?? 'none');
        $motionDelay = self::normalizeMotionDelay($attrs['motion-delay'] ?? 'normal');
        if ($motion !== 'none') {
            $classes[] = 'pg-motion';
            $classes[] = 'pg-motion--' . $motion;
            if ($motionDelay === 'short') {
                $classes[] = 'pg-motion-delay-short';
            }
        }

        $classAttr = htmlspecialchars(implode(' ', $classes), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<div class="' . $classAttr . '"><div class="pg-visual-frame__inner">'
            . $innerHtml
            . '</div></div>';
    }

    public static function normalizeAlign(string $raw): string
    {
        $value = strtolower(trim($raw));

        return in_array($value, self::ALIGNMENTS, true) ? $value : 'center';
    }

    public static function normalizeMaxWidth(string $raw): ?int
    {
        $trimmed = trim($raw);
        if ($trimmed === '' || !ctype_digit($trimmed)) {
            return null;
        }

        $width = (int) $trimmed;
        if ($width < self::MAX_WIDTH_MIN || $width > self::MAX_WIDTH_MAX) {
            return null;
        }

        return $width;
    }

    public static function normalizeTextSize(string $raw): string
    {
        $value = strtolower(trim($raw));

        return in_array($value, self::TEXT_SIZES, true) ? $value : 'md';
    }

    public static function normalizeTone(string $raw): string
    {
        $value = strtolower(trim($raw));

        return in_array($value, self::TONES, true) ? $value : 'default';
    }

    public static function normalizeMark(string $raw): string
    {
        $value = strtolower(trim($raw));

        return in_array($value, self::MARKS, true) ? $value : 'none';
    }

    public static function isTruthy(string $raw): bool
    {
        return filter_var(trim($raw), FILTER_VALIDATE_BOOLEAN);
    }

    public static function normalizeMotion(string $raw): string
    {
        $value = strtolower(trim($raw));

        return in_array($value, self::MOTIONS, true) ? $value : 'none';
    }

    public static function normalizeMotionDelay(string $raw): string
    {
        $value = strtolower(trim($raw));

        return in_array($value, self::MOTION_DELAYS, true) ? $value : 'normal';
    }
}
