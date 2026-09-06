<?php

declare(strict_types=1);

return [
    'not_found' => 'Médium nebolo nájdené',
    'file_required' => 'Súbor je povinný',
    'upload_failed' => 'Nepodarilo sa nahrať súbor',
    'deleted' => 'Médium bolo vymazané',
    'updated' => 'Médium bolo aktualizované',
    'invalid_type' => 'Nepodporovaný typ súboru',
    'folder_required' => 'Názov priečinka je povinný',
    'folder_created' => 'Priečinok bol vytvorený',
    'paths_required' => 'Zoznam ciest je povinný',
    'bulk_deleted' => 'Vybrané médiá boli vymazané',
    'stock_imported' => 'Stock obrázok bol importovaný do knižnice',
    'stock_disabled' => 'Import stock obrázkov je vypnutý v nastaveniach',
    'optimized' => 'Obrázok bol úspešne optimalizovaný',
    'optimize_gd_required' => 'Pre optimalizáciu obrázkov je potrebné PHP rozšírenie GD. Nainštalujte balík php-gd (alebo znovu zostavte Docker PHP image) a reštartujte PHP-FPM.',
    'optimize_gd_jpeg' => 'PHP GD nemá podporu JPEG. Preinštalujte php-gd s podporou JPEG.',
    'optimize_gd_png' => 'PHP GD nemá podporu PNG. Preinštalujte php-gd s podporou PNG.',
    'optimize_gd_webp' => 'PHP GD nemá podporu WebP. Preinštalujte php-gd s podporou WebP.',
    'optimize_empty' => 'Prázdny súbor obrázka.',
    'optimize_invalid' => 'Neplatný súbor obrázka.',
    'optimize_invalid_dimensions' => 'Neplatné rozmery obrázka.',
    'optimize_unsupported_type' => 'Manuálne sa dajú optimalizovať len JPEG, PNG a WebP.',
    'optimize_decode_failed' => 'Obrázok sa nepodarilo dekódovať pre optimalizáciu.',
    'optimize_encode_failed' => 'Optimalizovaný obrázok sa nepodarilo znovu zakódovať.',
    'optimize_no_reduction' => 'Obrázok je už optimálne komprimovaný (re-enkódovanie by nezmenšilo veľkosť).',
    'optimize_preview_expired' => 'Náhľad optimalizácie vypršal alebo je neplatný. Vygenerujte nový náhľad.',
];
