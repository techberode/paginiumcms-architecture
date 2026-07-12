<?php

declare(strict_types=1);

// ---- MANUÁLNE CORS HLAVIČKY ----
header('Access-Control-Allow-Origin: http://localhost:3025');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token, Accept, Origin');
header('Access-Control-Max-Age: 86400');

// Spracovanie OPTIONS preflight požiadaviek
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/../../vendor/autoload.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();

// ---- VŠETKY ENDPOINTY ----

// Root
$app->get('/', function ($request, $response) {
    $data = [
        'name' => 'PaginiumCMS API',
        'version' => '2.0.0',
        'status' => 'running'
    ];
    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

// CSRF token
$app->get('/api/auth/csrf-token', function ($request, $response) {
    $data = [
        'csrf_token' => 'test-token-' . bin2hex(random_bytes(8)),
          'expires_in' => 3600
    ];
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

// CSRF token - aj bez /api
$app->get('/auth/csrf-token', function ($request, $response) {
    $data = [
        'csrf_token' => 'test-token-' . bin2hex(random_bytes(8)),
          'expires_in' => 3600
    ];
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

// Aktuálny používateľ
$app->get('/auth/me', function ($request, $response) {
    $data = [
        'user' => [
            'id' => 1,
          'email' => 'test@example.com',
          'name' => 'Test User',
          'role' => 'ADMIN'
        ]
    ];
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

// Prihlásenie
$app->post('/auth/login', function ($request, $response) {
    $data = [
        'success' => true,
        'user' => [
            'id' => 1,
            'email' => 'test@example.com',
            'name' => 'Test User',
            'role' => 'ADMIN'
        ],
        'token' => 'test-token-' . bin2hex(random_bytes(16))
    ];
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

// Pages
$app->get('/api/pages', function ($request, $response) {
    $pages = [
        ['id' => 1, 'title' => 'Domovská stránka', 'slug' => 'home', 'content' => '<h1>Vitajte</h1><p>Obsah domovskej stránky.</p>'],
        ['id' => 2, 'title' => 'O nás', 'slug' => 'about', 'content' => '<h1>O nás</h1><p>Informácie o projekte.</p>']
    ];
    $response->getBody()->write(json_encode($pages, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/pages/{slug}', function ($request, $response, $args) {
    $slug = $args['slug'];
    $pages = [
        'home' => ['id' => 1, 'title' => 'Domovská stránka', 'slug' => 'home', 'content' => '<h1>Vitajte</h1><p>Obsah domovskej stránky.</p>'],
        'about' => ['id' => 2, 'title' => 'O nás', 'slug' => 'about', 'content' => '<h1>O nás</h1><p>Informácie o projekte.</p>']
    ];

    if (!isset($pages[$slug])) {
        $response->getBody()->write(json_encode(['error' => 'Stránka nenájdená']));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }

    $response->getBody()->write(json_encode($pages[$slug], JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

// Articles
$app->get('/api/articles', function ($request, $response) {
    $articles = [
        ['id' => 1, 'title' => 'Prvý článok', 'slug' => 'first-article', 'content' => '<h2>Prvý článok</h2><p>Obsah prvého článku.</p>'],
        ['id' => 2, 'title' => 'Druhý článok', 'slug' => 'second-article', 'content' => '<h2>Druhý článok</h2><p>Obsah druhého článku.</p>']
    ];
    $response->getBody()->write(json_encode($articles, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/articles/{slug}', function ($request, $response, $args) {
    $slug = $args['slug'];
    $articles = [
        'first-article' => ['id' => 1, 'title' => 'Prvý článok', 'slug' => 'first-article', 'content' => '<h2>Prvý článok</h2><p>Obsah prvého článku.</p>'],
        'second-article' => ['id' => 2, 'title' => 'Druhý článok', 'slug' => 'second-article', 'content' => '<h2>Druhý článok</h2><p>Obsah druhého článku.</p>']
    ];

    if (!isset($articles[$slug])) {
        $response->getBody()->write(json_encode(['error' => 'Článok nenájdený']));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }

    $response->getBody()->write(json_encode($articles[$slug], JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
