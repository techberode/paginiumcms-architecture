<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Support;

use PaginiumCMS\Core\Validation\ValidationException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Throwable;

/**
 * === Jednotný Error Handler ===
 * Centrálny prevod neošetrených výnimiek na jednotný JSON obal (Iterácia 4).
 *
 * Každá odpoveď má tvar `{ success: false, error: string, ... }`, takže
 * frontendový `apiClient.handleError` ju spracuje rovnako ako doteraz –
 * zavedenie je plne spätne kompatibilné. Mapovanie:
 *   ValidationException  → 422 (+ pole `errors`)
 *   Slim HttpException   → jeho stavový kód
 *   ostatné              → 500 (detaily len v debug režime)
 *
 * Registruje sa v `bootstrap/app.php` cez `setDefaultErrorHandler`.
 */
final class ApiErrorHandler
{
    public function __construct(private ResponseFactoryInterface $responseFactory)
    {
    }

    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface {
        [$status, $payload] = $this->map($exception, $displayErrorDetails);

        $response = $this->responseFactory->createResponse($status);
        $response->getBody()->write(
            (string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function map(Throwable $exception, bool $debug): array
    {
        if ($exception instanceof ValidationException) {
            return [422, [
                'success' => false,
                'error' => $exception->getMessage(),
                'errors' => $exception->getErrors(),
            ]];
        }

        if ($exception instanceof HttpException) {
            $status = $exception->getCode();
            if ($status < 100 || $status > 599) {
                $status = 500;
            }

            return [$status, [
                'success' => false,
                'error' => $exception->getMessage(),
            ]];
        }

        $payload = [
            'success' => false,
            'error' => $debug ? $exception->getMessage() : 'Vnútorná chyba servera',
        ];

        if ($debug) {
            $payload['exception'] = $exception::class;
            $payload['file'] = $exception->getFile() . ':' . $exception->getLine();
        }

        return [500, $payload];
    }
}
