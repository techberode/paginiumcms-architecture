<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use PaginiumCMS\Core\Layout\Services\ShortcodeDefinitionManager;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Admin API for shortcode definitions (It.67a).
 */
final class ShortcodeController
{
    public function __construct(
        private ShortcodeDefinitionManager $shortcodes,
        private JsonResponder $json,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, [
            'shortcodes' => $this->shortcodes->list(),
        ]);
    }

    /**
     * @param array<string, string> $args
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $name = (string) ($args['name'] ?? '');

        try {
            return $this->json->success($response, $this->shortcodes->get($name));
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 404);
        }
    }

    /**
     * @param array<string, string> $args
     */
    public function save(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $name = (string) ($args['name'] ?? '');
        $body = (string) $request->getBody();

        try {
            $saved = $this->shortcodes->save($name, $body);

            return $this->json->success($response, $saved, 200, 'Shortcode definition saved');
        } catch (CodePolicyViolationException $exception) {
            return $this->json->validation($response, $exception->getMessage(), $exception->getErrors());
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 422);
        }
    }

    public function preview(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = (string) $request->getBody();

        try {
            $definition = $this->shortcodes->preview($body);

            return $this->json->success($response, [
                'valid' => true,
                'definition' => $definition,
            ]);
        } catch (CodePolicyViolationException $exception) {
            return $this->json->validation($response, $exception->getMessage(), $exception->getErrors());
        } catch (\JsonException $exception) {
            return $this->json->error($response, 'Invalid JSON: ' . $exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, string> $args
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $name = (string) ($args['name'] ?? '');

        try {
            $this->shortcodes->delete($name);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 404);
        }

        return $this->json->success($response, ['name' => $name, 'removed' => true]);
    }
}
