<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Admin\Services;

use PaginiumCMS\Core\Backup\Contracts\BackupInterface;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Services\TrashService;
use PaginiumCMS\Modules\Comments\Contracts\CommentsRepositoryInterface;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;
use PaginiumCMS\Modules\Messages\Contracts\MessageRepositoryInterface;
use PaginiumCMS\Core\Security\Firewall\FirewallService;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserRepository;

/**
 * Aggregated admin entity counts (Iteration 42).
 */
final class AdminCountsService
{
    public function __construct(
        private ContentRepositoryInterface $content,
        private MediaRepositoryInterface $media,
        private CommentsRepositoryInterface $comments,
        private MessageRepositoryInterface $messages,
        private BackupInterface $backups,
        private TrashService $trash,
        private UserRepository $users,
        private FirewallService $firewall
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function collect(?User $viewer = null): array
    {
        $counts = [
            'pages' => count($this->content->findAllPages()),
            'articles' => count($this->content->findAllArticles()),
            'media' => count($this->media->findAll()),
            'backups' => count($this->backups->listBackups()),
        ];

        if ($this->viewerIsAdmin($viewer)) {
            $counts['comments'] = count($this->comments->findAll());
            $counts['messages'] = count($this->messages->findAll());
            $counts['trash'] = count($this->trash->listItems());
            $counts['users'] = count($this->users->findAll());
            $counts['firewall_jails'] = $this->firewall->countActiveJails();
        }

        return $counts;
    }

    private function viewerIsAdmin(?User $viewer): bool
    {
        if ($viewer === null) {
            return false;
        }

        foreach ($viewer->getRoles() as $role) {
            if (in_array($role, ['ADMIN', 'SUPER_ADMIN'], true)) {
                return true;
            }
        }

        return false;
    }
}
