<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Search;

/**
 * Statický katalóg admin modulov pre command palette (It.43).
 *
 * @return list<array{id: string, title: string, path: string, keywords: string, adminOnly: bool}>
 */
final class AdminRouteCatalog
{
    /**
     * @return list<array{id: string, title: string, path: string, keywords: string, adminOnly: bool}>
     */
    public static function routes(): array
    {
        return [
            ['id' => 'dashboard', 'title' => 'Prehľad', 'path' => '/dashboard', 'keywords' => 'dashboard home prehlad', 'adminOnly' => false],
            ['id' => 'pages', 'title' => 'Podstránky', 'path' => '/pages', 'keywords' => 'pages stranky content', 'adminOnly' => false],
            ['id' => 'articles', 'title' => 'Články (Blog)', 'path' => '/articles', 'keywords' => 'articles blog clanky', 'adminOnly' => false],
            ['id' => 'media', 'title' => 'Médiá', 'path' => '/media', 'keywords' => 'media obrazky subory upload', 'adminOnly' => false],
            ['id' => 'navigation', 'title' => 'Navigácia', 'path' => '/navigation', 'keywords' => 'navigation menu', 'adminOnly' => false],
            ['id' => 'comments', 'title' => 'Komentáre', 'path' => '/comments', 'keywords' => 'comments komentare', 'adminOnly' => true],
            ['id' => 'messages', 'title' => 'Správy', 'path' => '/messages', 'keywords' => 'messages inbox kontakt', 'adminOnly' => true],
            ['id' => 'github', 'title' => 'GitHub', 'path' => '/github', 'keywords' => 'github sync git', 'adminOnly' => true],
            ['id' => 'code-editor', 'title' => 'Code Editor', 'path' => '/code-editor', 'keywords' => 'code editor php', 'adminOnly' => false],
            ['id' => 'backups', 'title' => 'Zálohy', 'path' => '/backups', 'keywords' => 'backups zalohy', 'adminOnly' => false],
            ['id' => 'trash', 'title' => 'Kôš', 'path' => '/trash', 'keywords' => 'trash kos deleted', 'adminOnly' => true],
            ['id' => 'firewall', 'title' => 'Firewall', 'path' => '/firewall', 'keywords' => 'firewall waf security', 'adminOnly' => true],
            ['id' => 'logs', 'title' => 'Logy', 'path' => '/logs', 'keywords' => 'logs logging', 'adminOnly' => true],
            ['id' => 'audit', 'title' => 'Audit Trail', 'path' => '/audit', 'keywords' => 'audit trail history', 'adminOnly' => false],
            ['id' => 'notifications', 'title' => 'Notifikácie', 'path' => '/notifications', 'keywords' => 'notifications email ntfy', 'adminOnly' => false],
            ['id' => 'scheduler', 'title' => 'Plánovač', 'path' => '/scheduler', 'keywords' => 'scheduler cron jobs', 'adminOnly' => true],
            ['id' => 'users', 'title' => 'Používatelia', 'path' => '/users', 'keywords' => 'users pouzivatelia accounts', 'adminOnly' => true],
            ['id' => 'account-security', 'title' => 'Bezpečnosť účtu', 'path' => '/account/security', 'keywords' => '2fa password security', 'adminOnly' => false],
            ['id' => 'settings', 'title' => 'Nastavenia', 'path' => '/settings', 'keywords' => 'settings nastavenia smtp config', 'adminOnly' => false],
            ['id' => 'security-audit', 'title' => 'Bezpeč. audit', 'path' => '/security/audit', 'keywords' => 'security audit bezpecnost', 'adminOnly' => true],
            ['id' => 'security-acl', 'title' => 'ACL pravidlá', 'path' => '/security/acl', 'keywords' => 'acl pravidla permissions', 'adminOnly' => true],
            ['id' => 'blueprints', 'title' => 'Blueprinty', 'path' => '/blueprints', 'keywords' => 'blueprints sablony', 'adminOnly' => true],
            ['id' => 'demo', 'title' => 'Demo modul', 'path' => '/demo', 'keywords' => 'demo modul test', 'adminOnly' => true],
            ['id' => 'developer-logs', 'title' => 'Developer logy', 'path' => '/developer/logs', 'keywords' => 'developer logs debug', 'adminOnly' => true],
        ];
    }
}
