<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\HybridEngine\QueryIndex;

use PDO;

/**
 * Versioned derived index schema (It.92b). Migration = drop file + rebuild.
 */
final class QueryIndexSchema
{
    public const SCHEMA_VERSION = 1;

    public static function apply(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS query_index_meta (
                key TEXT PRIMARY KEY NOT NULL,
                value TEXT NOT NULL
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS entries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT NOT NULL,
                type TEXT NOT NULL,
                title TEXT NOT NULL,
                status TEXT NOT NULL,
                author TEXT NOT NULL,
                path TEXT NOT NULL,
                excerpt TEXT NOT NULL,
                category TEXT NOT NULL DEFAULT \'\',
                updated_at TEXT NOT NULL,
                created_at TEXT NOT NULL,
                scheduled_at TEXT NOT NULL DEFAULT \'\',
                last_reviewed_at TEXT NOT NULL DEFAULT \'\',
                default_locale TEXT NOT NULL DEFAULT \'sk\',
                tags_json TEXT NOT NULL DEFAULT \'[]\',
                locales_json TEXT NOT NULL DEFAULT \'[]\',
                locale_status_json TEXT NOT NULL DEFAULT \'{}\',
                calendar_date TEXT NOT NULL DEFAULT \'\',
                UNIQUE(type, slug)
            )'
        );

        $pdo->exec(
            'CREATE VIRTUAL TABLE IF NOT EXISTS entries_fts USING fts5(
                title,
                slug,
                excerpt,
                tags_text,
                tokenize = "unicode61 remove_diacritics 2"
            )'
        );

        $stmt = $pdo->prepare('INSERT OR REPLACE INTO query_index_meta (key, value) VALUES (:key, :value)');
        $stmt->execute(['key' => 'schema_version', 'value' => (string) self::SCHEMA_VERSION]);
    }

    public static function wipeDerivedTables(PDO $pdo): void
    {
        $pdo->exec('DELETE FROM entries_fts');
        $pdo->exec('DELETE FROM entries');
    }
}
