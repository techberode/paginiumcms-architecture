<?php

declare(strict_types=1);

use PaginiumCMS\Core\Scheduler\Handlers\SystemDeployHandler;
use PaginiumCMS\Core\SystemUpdate\Commands\SystemDeployCommand;
use PaginiumCMS\Core\SystemUpdate\Services\GitHubReleaseClient;
use PaginiumCMS\Core\SystemUpdate\Services\GitRepositoryInspector;
use PaginiumCMS\Core\SystemUpdate\Services\SystemDeployService;
use PaginiumCMS\Core\SystemUpdate\Services\SystemUpdateVersionMatcher;
use PaginiumCMS\Http\Controllers\Admin\SystemUpdateController;

use function DI\create;
use function DI\get;

return [
    GitRepositoryInspector::class => create(GitRepositoryInspector::class),
    GitHubReleaseClient::class => create(GitHubReleaseClient::class),
    SystemUpdateVersionMatcher::class => create(SystemUpdateVersionMatcher::class),
    SystemDeployService::class => create(SystemDeployService::class)
        ->constructor(get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class)),
    SystemDeployHandler::class => create(SystemDeployHandler::class)
        ->constructor(get(SystemDeployService::class)),
    SystemDeployCommand::class => create(SystemDeployCommand::class)
        ->constructor(get(SystemDeployService::class)),
    SystemUpdateController::class => create(SystemUpdateController::class)
        ->constructor(
            get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class),
            get(GitRepositoryInspector::class),
            get(GitHubReleaseClient::class),
            get(SystemDeployService::class),
            get(\PaginiumCMS\Core\Scheduler\Services\JobRegistryStore::class),
            get(\PaginiumCMS\Core\Scheduler\Services\JobRunStore::class),
            get(\PaginiumCMS\Core\Scheduler\Services\JobQueueStore::class),
            get(\PaginiumCMS\Core\Scheduler\Services\JobWorker::class),
            get(\PaginiumCMS\Modules\Security\Services\SecurityAuditStore::class),
            get(SystemUpdateVersionMatcher::class),
            get(\PaginiumCMS\Http\Support\JsonResponder::class)
        ),
];
