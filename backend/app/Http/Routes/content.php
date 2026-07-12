<?php

declare(strict_types=1);

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return function (App $app): void {
    // Testovací endpoint - overenie že API funguje
    $app->get('/api/test', function (Request $request, Response $response) {
        $data = [
            'status' => 'ok',
            'message' => 'API beží!',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Získanie všetkých stránok
    $app->get('/api/pages', function (Request $request, Response $response) {
        $pages = [
            [
                'id' => 1,
                'title' => 'Domovská stránka',
                'slug' => 'home',
                'content' => '<h1>Vitajte na PaginiumCMS</h1><p>Toto je testovacia stránka.</p>',
                'published' => true,
                'created_at' => '2026-07-11 12:00:00'
            ],
            [
                'id' => 2,
                'title' => 'O nás',
                'slug' => 'about',
                'content' => '<h1>O nás</h1><p>Informácie o projekte.</p>',
                'published' => true,
                'created_at' => '2026-07-11 12:30:00'
            ]
        ];

        $response->getBody()->write(json_encode($pages, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Získanie jednej stránky podľa ID alebo slugu
    $app->get('/api/pages/{identifier}', function (Request $request, Response $response, array $args) {
        $identifier = $args['identifier'];

        $pages = [
            'home' => [
                'id' => 1,
              'title' => 'Domovská stránka',
              'slug' => 'home',
              'content' => '<h1>Vitajte na PaginiumCMS</h1><p>Toto je testovacia stránka.</p>',
              'published' => true
            ],
            'about' => [
                'id' => 2,
              'title' => 'O nás',
              'slug' => 'about',
              'content' => '<h1>O nás</h1><p>Informácie o projekte.</p>',
              'published' => true
            ]
        ];

        if (!isset($pages[$identifier])) {
            $response->getBody()->write(json_encode(['error' => 'Stránka nenájdená']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($pages[$identifier], JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Získanie všetkých článkov
    $app->get('/api/articles', function (Request $request, Response $response) {
        $articles = [
            [
                'id' => 1,
                'title' => 'Prvý článok',
                'slug' => 'first-article',
                'content' => '<h2>Prvý článok</h2><p>Toto je obsah prvého článku.</p>',
                'excerpt' => 'Úryvok prvého článku.',
                'date' => '2026-07-11',
                'published' => true
            ],
            [
                'id' => 2,
                'title' => 'Druhý článok',
                'slug' => 'second-article',
                'content' => '<h2>Druhý článok</h2><p>Toto je obsah druhého článku.</p>',
                'excerpt' => 'Úryvok druhého článku.',
                'date' => '2026-07-10',
                'published' => true
            ]
        ];

        $response->getBody()->write(json_encode($articles, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Získanie jedného článku podľa slugu
    $app->get('/api/articles/{slug}', function (Request $request, Response $response, array $args) {
        $slug = $args['slug'];

        $articles = [
            'first-article' => [
                'id' => 1,
                'title' => 'Prvý článok',
                'slug' => 'first-article',
                'content' => '<h2>Prvý článok</h2><p>Toto je obsah prvého článku.</p>',
                'excerpt' => 'Úryvok prvého článku.',
                'date' => '2026-07-11',
                'published' => true
            ],
            'second-article' => [
                'id' => 2,
                'title' => 'Druhý článok',
                'slug' => 'second-article',
                'content' => '<h2>Druhý článok</h2><p>Toto je obsah druhého článku.</p>',
                'excerpt' => 'Úryvok druhého článku.',
                'date' => '2026-07-10',
                'published' => true
            ]
        ];

        if (!isset($articles[$slug])) {
            $response->getBody()->write(json_encode(['error' => 'Článok nenájdený']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($articles[$slug], JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Root endpoint
    $app->get('/', function (Request $request, Response $response) {
        $data = [
            'name' => 'PaginiumCMS API',
            'version' => '2.0.0',
            'status' => 'running',
            'endpoints' => [
                '/api/test' => 'Testovací endpoint',
                '/api/pages' => 'Zoznam stránok',
                '/api/pages/{slug}' => 'Detail stránky',
                '/api/articles' => 'Zoznam článkov',
                '/api/articles/{slug}' => 'Detail článku',
                '/api/auth/login' => 'Prihlásenie',
                '/api/auth/register' => 'Registrácia',
                '/api/auth/me' => 'Informácie o prihlásenom používateľovi'
            ]
        ];
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    });
};
