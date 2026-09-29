<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Editor\Services;

use PaginiumCMS\Core\Media\Services\DamMediaUrl;

/**
 * Inline prose figures (captioned images) allowed in Markdown storage (It.79+).
 */
final class ProseFigureMarkup
{
    public function stripBlocks(string $markdown): string
    {
        $withoutFigures = preg_replace('/<figure\s+class="paginium-figure"[\s\S]*?<\/figure>/i', '', $markdown);
        if (!is_string($withoutFigures)) {
            $withoutFigures = $markdown;
        }

        $withoutInline = preg_replace(
            '/<img\s[^>]*class="[^"]*max-w-full[^"]*"[^>]*\/?>\s*/i',
            '',
            $withoutFigures
        );

        return is_string($withoutInline) ? $withoutInline : $withoutFigures;
    }

    public function validateBlocks(string $markdown): ?string
    {
        if (
            preg_match_all('/<figure\s+class="paginium-figure"[\s\S]*?<\/figure>/i', $markdown, $figureMatches) !== false
        ) {
            foreach ($figureMatches[0] as $block) {
                if (preg_match('/<(script|iframe|object|embed|form)\b/i', $block) === 1) {
                    return 'Popis obrázka nesmie obsahovať vložené skripty alebo iframe.';
                }

                if (preg_match('/<img[^>]+src="([^"]+)"/i', $block, $imgMatch) !== 1) {
                    return 'Figure obrázka musí obsahovať platný obrázok z Media Library.';
                }

                if (DamMediaUrl::sanitize($imgMatch[1]) === '') {
                    return 'Figure obrázka musí používať URL z Media Library.';
                }
            }
        }

        if (
            preg_match_all('/<img\s[^>]*class="[^"]*max-w-full[^"]*"[^>]*>/i', $markdown, $inlineMatches) !== false
        ) {
            foreach ($inlineMatches[0] as $tag) {
                if (preg_match('/src="([^"]+)"/i', $tag, $srcMatch) !== 1) {
                    return 'Inline obrázok musí mať src z Media Library.';
                }

                if (DamMediaUrl::sanitize($srcMatch[1]) === '') {
                    return 'Inline obrázok musí používať URL z Media Library.';
                }
            }
        }

        return null;
    }
}
