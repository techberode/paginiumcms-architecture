<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\AuditTrail\Services;

use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Core\Logging\Models\LogEntry;
use PaginiumCMS\Core\Logging\Models\LogSeverity;
use PaginiumCMS\Core\Versioning\Services\EnhancedVersionManager;
use PaginiumCMS\Core\Versioning\Models\Version;
use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Modules\Security\Models\User;


class AuditTrailService
{
    private LoggerInterface $logger;
    private EnhancedVersionManager $versionManager;
    private UserRepository $userRepository;
    private ?IncidentNotifier $incidentNotifier;
    /** @var array<int|string, mixed> */
    private array $sessionContext = [];
    /** @var array<int|string, mixed> */
    private array $auditBuffer = [];
    private bool $isBuffering = false;

    public function __construct(
        LoggerInterface $logger,
        EnhancedVersionManager $versionManager,
        UserRepository $userRepository,
        ?IncidentNotifier $incidentNotifier = null
    ) {
        $this->logger = $logger;
        $this->versionManager = $versionManager;
        $this->userRepository = $userRepository;
        $this->incidentNotifier = $incidentNotifier;
        
        $this->initializeSessionContext();
    }

    /**
     * Inicializuje kontext session
     */
    private function initializeSessionContext(): void
    {
        $sessionId = session_id();

        $this->sessionContext = [
            'session_id' => $sessionId !== '' ? $sessionId : 'unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s'),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
        ];

        // Pridanie používateľa ak je prihlásený
        if (isset($_SESSION['user_id'])) {
            $user = $this->userRepository->findById($_SESSION['user_id']);
            if ($user) {
                $this->sessionContext['user'] = [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getName(),
                    'roles' => $user->getRoles()
                ];
            }
        }
    }

    /**
     * Zaznamená zmenu obsahu s prepojením na verziu
 * @param array<int|string, mixed> $metadata
 */public function logContentChange(
        string $contentId,
        string $contentType,
        string $action,
        string $content,
        string $frontMatter,
        ?User $user = null,
        string $message = '',
        array $metadata = []
    ): Version {
        // Vytvorenie verzie
        $userId = $user ? $user->getId() : 'system';
        $version = $this->versionManager->createVersion(
            $contentId,
            $contentType,
            $content,
            $frontMatter,
            $userId,
            $message
        );

        // Získanie predchádzajúcej verzie pre porovnanie (verzia 1 nemá predchodcu)
        $previousVersion = $version->getVersion() > 1
            ? $this->versionManager->getVersion($contentId, $version->getVersion() - 1)
            : null;

        // Zaznamenanie audit logu
        $this->logAuditEvent(
            'content_change',
            $contentId,
            $action,
            $user,
            array_merge([
                'version' => $version->getVersion(),
                'content_type' => $contentType,
                'message' => $message,
                'previous_version' => $previousVersion ? $previousVersion->getVersion() : null,
                'diff' => $version->getDiff(),
                'change_summary' => $this->summarizeDiff($version->getDiff()),
                'content_size' => strlen($content),
                'front_matter_size' => strlen($frontMatter)
            ], $metadata)
        );

        // Uloženie do bufferu pre batch spracovanie
        $this->bufferAuditEvent([
            'type' => 'content_change',
            'content_id' => $contentId,
            'version' => $version->getVersion(),
            'user_id' => $userId,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        return $version;
    }

    /**
     * Zaznamená prístup k obsahu
 * @param array<int|string, mixed> $metadata
 */public function logContentAccess(
        string $contentId,
        string $contentType,
        string $action,
        ?User $user = null,
        array $metadata = []
    ): void {
        $this->logAuditEvent(
            'content_access',
            $contentId,
            $action,
            $user,
            array_merge([
                'content_type' => $contentType,
                'access_type' => 'read'
            ], $metadata)
        );
    }

    /**
     * Zaznamená administrátorskú akciu
 * @param array<int|string, mixed> $metadata
 */public function logAdminAction(
        string $action,
        string $target,
        ?User $user = null,
        array $metadata = [],
        string $severity = LogSeverity::INFO
    ): void {
        $this->logAuditEvent(
            'admin_action',
            $target,
            $action,
            $user,
            $metadata,
            $severity
        );
    }

    /**
     * Zaznamená bezpečnostnú udalosť
 * @param array<int|string, mixed> $metadata
 */public function logSecurityEvent(
        string $action,
        string $target,
        ?User $user = null,
        array $metadata = [],
        string $severity = LogSeverity::WARNING
    ): void {
        $this->logAuditEvent(
            'security',
            $target,
            $action,
            $user,
            $metadata,
            $severity
        );

        if ($this->incidentNotifier !== null) {
            $details = $action . ' on ' . $target;
            if ($metadata !== []) {
                $details .= ' | ' . json_encode($metadata, JSON_UNESCAPED_UNICODE);
            }
            $this->incidentNotifier->notifySecurityEvent($action, $details, $this->mapSeverity($severity));
        }
    }

    private function mapSeverity(string $severity): string
    {
        return match (strtoupper($severity)) {
            LogSeverity::CRITICAL => 'critical',
            LogSeverity::ERROR => 'error',
            LogSeverity::INFO, LogSeverity::DEBUG => 'info',
            default => 'warning',
        };
    }

    /**
     * Hlavná metóda pre logovanie audit udalostí
 * @param array<int|string, mixed> $metadata
 */private function logAuditEvent(
        string $category,
        string $target,
        string $action,
        ?User $user = null,
        array $metadata = [],
        string $severity = LogSeverity::INFO
    ): void {
        $userInfo = $user ? [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'roles' => $user->getRoles()
        ] : null;

        $context = array_merge(
            $this->sessionContext,
            [
                'category' => $category,
                'target' => $target,
                'action' => $action,
                'user' => $userInfo,
                'metadata' => $metadata,
                'severity' => $severity,
                'timestamp' => date('Y-m-d H:i:s'),
                'audit_id' => uniqid('audit_', true)
            ]
        );

        $message = sprintf(
            '[%s] %s: %s on %s by %s',
            strtoupper($category),
            strtoupper($action),
            $target,
            $user ? $user->getEmail() : 'system',
            date('Y-m-d H:i:s')
        );

        $entry = new LogEntry($severity, 'audit_' . $category, $message);
        $entry->setContext($context);

        $this->logger->log($severity, $message, $context);

        // Ak je buffering zapnutý, uložíme do bufferu
        if ($this->isBuffering) {
            $this->auditBuffer[] = [
                'entry' => $entry,
                'context' => $context,
                'timestamp' => time()
            ];
        }
    }

    /**
     * Získa kompletný audit trail pre konkrétny obsah
 * @return array<int|string, mixed>
 */public function getContentAuditTrail(string $contentId, int $limit = 100): array
    {
        $auditLogs = [];
        
        // Získanie verzií
        $versions = $this->versionManager->getVersions($contentId);
        
        // Získanie logov
        $logs = $this->logger->getEntriesByCategory('audit_content_change', 1000);
        
        // Filtrovanie logov pre konkrétny obsah
        $contentLogs = array_filter($logs, function($log) use ($contentId) {
            return isset($log['context']['target']) && $log['context']['target'] === $contentId;
        });

        // Spojenie verzií a logov
        foreach ($versions as $version) {
            $versionLog = array_filter($contentLogs, function($log) use ($version) {
                return isset($log['context']['metadata']['version']) && 
                       $log['context']['metadata']['version'] === $version->getVersion();
            });
            
            $versionData = $version->toArray();
            $auditLogs[] = [
                'type' => 'version',
                'version' => $versionData,
                'log' => !empty($versionLog) ? array_shift($versionLog) : null,
                'timestamp' => $version->getCreatedAt(),
                'user' => $this->getUserInfo($version->getCreatedBy())
            ];
        }

        // Pridanie logov bez verzií (prístupy, administrátorské akcie)
        foreach ($contentLogs as $log) {
            if (!isset($log['context']['metadata']['version'])) {
                $auditLogs[] = [
                    'type' => 'access',
                    'log' => $log,
                    'timestamp' => $log['timestamp'] ?? date('Y-m-d H:i:s'),
                    'user' => $log['context']['user'] ?? null
                ];
            }
        }

        // Zoradenie podľa času
        usort($auditLogs, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return array_slice($auditLogs, 0, $limit);
    }

    /**
     * Získa kompletný audit trail pre používateľa
 * @return array<int|string, mixed>
 */public function getUserAuditTrail(string $userId, int $limit = 100): array
    {
        $logs = $this->logger->getLastEntries(1000);
        
        $userLogs = array_filter($logs, function($log) use ($userId) {
            return isset($log['context']['user']['id']) && 
                   $log['context']['user']['id'] === $userId;
        });

        return array_slice($userLogs, 0, $limit);
    }

    /**
     * Získa štatistiky auditu
 * @param array<int|string, mixed> $filters
 * @return array<int|string, mixed>
 */public function getAuditStats(array $filters = []): array
    {
        $stats = [
            'total_events' => 0,
            'by_category' => [],
            'by_action' => [],
            'by_user' => [],
            'by_severity' => [],
            'recent_events' => [],
            'timeline' => []
        ];

        $logs = $this->logger->getLastEntries(1000);

        foreach ($logs as $log) {
            if (strpos($log['category'] ?? '', 'audit_') !== 0) {
                continue;
            }

            // Aplikovanie filtrov
            if ($filters) {
                $skip = false;
                foreach ($filters as $key => $value) {
                    if (($log['context'][$key] ?? null) !== $value) {
                        $skip = true;
                        break;
                    }
                }
                if ($skip) continue;
            }

            $stats['total_events']++;
            
            $category = str_replace('audit_', '', $log['category'] ?? 'unknown');
            $stats['by_category'][$category] = ($stats['by_category'][$category] ?? 0) + 1;
            
            $action = $log['context']['action'] ?? 'unknown';
            $stats['by_action'][$action] = ($stats['by_action'][$action] ?? 0) + 1;
            
            $severity = $log['severity'] ?? 'INFO';
            $stats['by_severity'][$severity] = ($stats['by_severity'][$severity] ?? 0) + 1;
            
            if (isset($log['context']['user']['email'])) {
                $email = $log['context']['user']['email'];
                $stats['by_user'][$email] = ($stats['by_user'][$email] ?? 0) + 1;
            }

            // Timeline - posledných 7 dní
            $date = date('Y-m-d', strtotime($log['timestamp'] ?? 'now'));
            $stats['timeline'][$date] = ($stats['timeline'][$date] ?? 0) + 1;

            // Recent events
            if (count($stats['recent_events']) < 20) {
                $stats['recent_events'][] = $log;
            }
        }

        // Zoradenie timeline
        ksort($stats['timeline']);

        // Zoradenie používateľov podľa aktivity
        arsort($stats['by_user']);

        return $stats;
    }

    /**
     * Export auditu do CSV
 * @param array<int|string, mixed> $filters
 */public function exportAuditToCsv(array $filters = []): string
    {
        $logs = $this->logger->getLastEntries(10000);
        $csv = "Timestamp,Category,Action,Target,User,Email,Severity,Message\n";

        foreach ($logs as $log) {
            if (strpos($log['category'] ?? '', 'audit_') !== 0) {
                continue;
            }

            $context = $log['context'] ?? [];
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,\"%s\"\n",
                $log['timestamp'] ?? '',
                str_replace('audit_', '', $log['category'] ?? ''),
                $context['action'] ?? '',
                $context['target'] ?? '',
                $context['user']['name'] ?? 'system',
                $context['user']['email'] ?? '',
                $log['severity'] ?? 'INFO',
                str_replace('"', '""', $log['message'] ?? '')
            );
        }

        return $csv;
    }

    /**
     * Bufferovanie audit udalostí
     */
    public function startBuffering(): void
    {
        $this->isBuffering = true;
        $this->auditBuffer = [];
    }

    public function stopBuffering(): void
    {
        $this->isBuffering = false;
        
        // Spracovanie bufferu
        foreach ($this->auditBuffer as $buffered) {
            $this->processBufferedEvent($buffered);
        }
        
        $this->auditBuffer = [];
    }

    /**
     * @param array<int|string, mixed> $event
     */
    private function bufferAuditEvent(array $event): void
    {
        if ($this->isBuffering) {
            $this->auditBuffer[] = $event;
        }
    }

    /**
     * @param array<int|string, mixed> $event
     */
    private function processBufferedEvent(array $event): void
    {
        // Spracovanie bufferovanej udalosti - napr. odoslanie do externého systému
        // Tu môžete pridať ďalšiu logiku ako odoslanie do SIEM, Slack notifikácie, atď.
    }

    /**
     * @return array{id: string, email: string, name: string}|null
     */
    private function getUserInfo(string $userId): ?array
    {
        $user = $this->userRepository->findById($userId);
        return $user ? [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName()
        ] : null;
    }

    /**
     * @param array<int|string, mixed> $diff
     */
    private function summarizeDiff(?array $diff): string
    {
        if (!$diff) {
            return 'No changes';
        }

        $summary = [];
        if (isset($diff['additions']) && $diff['additions'] > 0) {
            $summary[] = $diff['additions'] . ' added';
        }
        if (isset($diff['deletions']) && $diff['deletions'] > 0) {
            $summary[] = $diff['deletions'] . ' removed';
        }
        if (isset($diff['modifications']) && $diff['modifications'] > 0) {
            $summary[] = $diff['modifications'] . ' modified';
        }

        return empty($summary) ? 'No significant changes' : implode(', ', $summary);
    }
}
