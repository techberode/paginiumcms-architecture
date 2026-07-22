<?php

declare(strict_types=1);

/**
 * Built-in WAF scenarios (Iteration 50 + S-WAFBODY body scanning).
 * Loaded via OPcache — no per-request disk I/O beyond include.
 *
 * @return array<string, array{
 *     id: string,
 *     label: string,
 *     targets: list<'uri'|'user_agent'|'query'|'body'>,
 *     pattern: string,
 *     severity: string,
 *     enabled: bool
 * }>
 */
return [
    'wp_probe' => [
        'id' => 'wp_probe',
        'label' => 'WordPress probe',
        'targets' => ['uri'],
        'pattern' => '#/(wp-admin|wp-login\.php|xmlrpc\.php)(/|$|\?)#i',
        'severity' => 'high',
        'enabled' => true,
    ],
    'env_probe' => [
        'id' => 'env_probe',
        'label' => 'Environment / secrets probe',
        'targets' => ['uri', 'body'],
        'pattern' => '#(/\.env|/config\.php\.bak|/\.git/|/\.htaccess)(/|$|\?)#i',
        'severity' => 'critical',
        'enabled' => true,
    ],
    'path_traversal' => [
        'id' => 'path_traversal',
        'label' => 'Path traversal',
        'targets' => ['uri', 'query', 'body'],
        'pattern' => '#(\.\./|%2e%2e/|%2e%2e%2f|\.\.%2f)#i',
        'severity' => 'high',
        'enabled' => true,
    ],
    'sql_probe_uri' => [
        'id' => 'sql_probe_uri',
        'label' => 'SQL injection probe (URI/query)',
        'targets' => ['uri', 'query'],
        'pattern' => '#(union\s+select|select\s+.+\s+from|insert\s+into|drop\s+table|;\s*--|or\s+1\s*=\s*1)#i',
        'severity' => 'high',
        'enabled' => true,
    ],
    'sql_probe_body' => [
        'id' => 'sql_probe_body',
        'label' => 'SQL injection probe (POST body)',
        'targets' => ['body'],
        'pattern' => '#(union\s+select|select\s+.+\s+from|insert\s+into|drop\s+table|;\s*--|or\s+1\s*=\s*1)#i',
        'severity' => 'high',
        'enabled' => true,
    ],
    'ssrf_probe_body' => [
        'id' => 'ssrf_probe_body',
        'label' => 'Dangerous URL scheme in body',
        'targets' => ['body'],
        'pattern' => '#\b(?:file|php|phar|gopher|dict|ftp)://#i',
        'severity' => 'high',
        'enabled' => true,
    ],
    'bad_bot_ua' => [
        'id' => 'bad_bot_ua',
        'label' => 'Suspicious empty user-agent',
        'targets' => ['user_agent'],
        'pattern' => '#^$#',
        'severity' => 'medium',
        'enabled' => true,
    ],
];
