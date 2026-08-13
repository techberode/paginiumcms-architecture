<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Middleware pre bezpečnostné hlavičky a ochranu.
 */
final class SecurityMiddleware implements MiddlewareInterface
{
    /** @var array<int|string, mixed> */
    private array $config;

    /**
     * @param array<int|string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'hsts_max_age' => 31536000,
            'csp_default' => "default-src 'self'",
            // script-src bez 'unsafe-inline' – Vite build servíruje len externé
            // module skripty, takže inline skripty nie sú potrebné (tvrdšia XSS ochrana).
            'csp_script' => "script-src 'self'",
            // style-src ponecháva 'unsafe-inline' kvôli inline style atribútom (React/knižnice).
            'csp_style' => "style-src 'self' 'unsafe-inline'",
            'csp_img' => "img-src 'self' data: https:",
            'csp_font' => "font-src 'self' data:",
            'csp_connect' => "connect-src 'self'",
            'csp_worker' => "worker-src 'self' blob:",
            'csp_frame_ancestors' => "frame-ancestors 'none'",
            'csp_base_uri' => "base-uri 'self'",
            'csp_form_action' => "form-action 'self'",
            'frame_options' => 'DENY',
            'xss_protection' => '1; mode=block',
            'content_type' => 'nosniff',
            'referrer_policy' => 'strict-origin-when-cross-origin',
            'permissions_policy' => "geolocation=(), microphone=(), camera=(), payment=()",
            'remove_server_headers' => true,
        ], $config);
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $response = $handler->handle($request);

        // Aplikujeme bezpečnostné hlavičky
        $response = $this->applySecurityHeaders($request, $response);

        // Odstránime citlivé informácie
        if ($this->config['remove_server_headers']) {
            $response = $this->removeSensitiveHeaders($response);
        }

        return $response;
    }

    private function applySecurityHeaders(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $isHttps = (!empty($request->getServerParams()['HTTPS'])
                && $request->getServerParams()['HTTPS'] !== 'off')
            || strtolower($request->getHeaderLine('X-Forwarded-Proto')) === 'https';

        if ($isHttps) {
            $response = $response->withHeader(
                'Strict-Transport-Security',
                sprintf('max-age=%d; includeSubDomains; preload', $this->config['hsts_max_age'])
            );
        }

        // CSP (Content Security Policy)
        $csp = implode('; ', [
            $this->config['csp_default'],
            $this->config['csp_script'],
            $this->config['csp_style'],
            $this->config['csp_img'],
            $this->config['csp_font'],
            $this->config['csp_connect'],
            $this->config['csp_worker'],
            $this->config['csp_frame_ancestors'],
            $this->config['csp_base_uri'],
            $this->config['csp_form_action'],
        ]);
        $response = $response->withHeader('Content-Security-Policy', $csp);

        // Ďalšie hlavičky
        $response = $response
            ->withHeader('X-Frame-Options', $this->config['frame_options'])
            ->withHeader('X-XSS-Protection', $this->config['xss_protection'])
            ->withHeader('X-Content-Type-Options', $this->config['content_type'])
            ->withHeader('Referrer-Policy', $this->config['referrer_policy'])
            ->withHeader('Permissions-Policy', $this->config['permissions_policy']);

        return $response;
    }

    private function removeSensitiveHeaders(ResponseInterface $response): ResponseInterface
    {
        $sensitiveHeaders = [
            'X-Powered-By',
            'Server',
            'X-AspNet-Version',
            'X-AspNetMvc-Version',
        ];

        foreach ($sensitiveHeaders as $header) {
            if ($response->hasHeader($header)) {
                $response = $response->withoutHeader($header);
            }
        }

        return $response;
    }
}
