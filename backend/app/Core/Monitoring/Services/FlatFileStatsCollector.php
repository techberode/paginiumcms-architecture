<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Monitoring\Services;

use PaginiumCMS\Core\Backup\Contracts\BackupInterface;
use PaginiumCMS\Core\Conflict\Contracts\ConflictLoggerInterface;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Services\TrashService;
use PaginiumCMS\Core\Locking\Contracts\LockManagerInterface;
use PaginiumCMS\Modules\Security\Services\UserRepository;

/**
 * Aggregated flat-file entity counts for monitoring reports (Iteration 7).
 */
final class FlatFileStatsCollector
{
    public function __construct(
        private ContentRepositoryInterface $content,
        private UserRepository $users,
        private BackupInterface $backups,
        private TrashService $trash,
        private LockManagerInterface $locks,
        private ConflictLoggerInterface $conflicts
    ) {
    }

    /**
     * @return array<string, int|string>
     */
    public function collect(): array
    {
        return [
            'pages' => count($this->content->findAllPages()),
            'articles' => count($this->content->findAllArticles()),
            'users' => count($this->users->findAll()),
            'backups' => count($this->backups->listBackups()),
            'trash_items' => count($this->trash->listItems()),
            'active_locks' => count($this->locks->getAllLocks()),
            'conflicts_logged' => count($this->conflicts->getRecent(500)),
        ];
    }
}
