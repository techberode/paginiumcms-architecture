<?php

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return function (App $app) {
    // Testovacie routy
    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('✅ PaginiumCMS Backend is running!');
        return $response;
    });

    $app->get('/health', function (Request $request, Response $response) {
        $data = [
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
              'version' => '1.0.0',
              'php_version' => PHP_VERSION,
        ];
        $response->getBody()->write((string) json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->get('/ping', function (Request $request, Response $response) {
        $response->getBody()->write('pong');
        return $response;
    });
};
