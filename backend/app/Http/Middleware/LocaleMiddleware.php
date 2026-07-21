<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Core\I18n\Services\SupportedLocalesRegistry;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Nastaví locale pre Lang podľa CMS nastavení a Accept-Language hlavičky.
 */
final class LocaleMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly SettingsRepositoryInterface $settings,
        private readonly SupportedLocalesRegistry $locales
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        Lang::setSupportedLocales($this->locales->codes());
        Lang::setLocale($this->resolveLocale($request));

        return $handler->handle($request);
    }

    private function resolveLocale(ServerRequestInterface $request): string
    {
        $configured = (string) $this->settings->get('general.language', 'sk');
        if ($this->isSupported($configured)) {
            return $configured;
        }

        $header = $request->getHeaderLine('Accept-Language');
        if ($header !== '') {
            foreach (explode(',', $header) as $part) {
                $tag = strtolower(trim(explode(';', $part)[0]));
                $short = substr($tag, 0, 2);
                if ($this->isSupported($short)) {
                    return $short;
                }
            }
        }

        return 'sk';
    }

    private function isSupported(string $locale): bool
    {
        return $this->locales->isSupported($locale);
    }
}
