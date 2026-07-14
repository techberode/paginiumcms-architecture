<?php

declare(strict_types=1);

// 1. Načítanie UTF-8 nastavení (definuje utf8_normalize())
require_once __DIR__ . '/../backend/bootstrap/utf8.php';

// 2. Načítanie autoloaderu
require_once __DIR__ . '/../vendor/autoload.php';

// 3. Manuálne načítanie chýbajúcich rozhraní (ak treba)
if (!interface_exists('PaginiumCMS\Core\FlatFile\Contracts\MarkdownContentParserInterface')) {
    require_once __DIR__ . '/../backend/app/Core/FlatFile/Contracts/MarkdownContentParserInterface.php';
}
