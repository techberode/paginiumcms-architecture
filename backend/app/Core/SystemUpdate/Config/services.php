<?php

declare(strict_types=1);

use PaginiumCMS\Core\Scheduler\Handlers\SystemDeployHandler;
use PaginiumCMS\Core\SystemUpdate\Commands\SystemDeployCommand;
use PaginiumCMS\Core\SystemUpdate\Services\GitHubReleaseClient;
use PaginiumCMS\Core\SystemUpdate\Services\GitHubReleaseWebhookVerifier;
use PaginiumCMS\Core\SystemUpdate\Services\GitRepositoryInspector;
use PaginiumCMS\Core\SystemUpdate\Services\SystemDeployService;
use PaginiumCMS\Core\SystemUpdate\Services\SystemDeployTriggerService;
use PaginiumCMS\Core\SystemUpdate\Services\SystemUpdateVersionMatcher;
use PaginiumCMS\Core\SystemUpdate\Services\SystemUpdateWebhookService;
use PaginiumCMS\Http\Controllers\Admin\SystemUpdateController;
use PaginiumCMS\Http\Controllers\Webhooks\GitHubReleaseWebhookController;

use function DI\create;
use function DI\get;

return [
    GitRepositoryInspector::class => create(GitRepositoryInspector::class),
    GitHubReleaseClient::class => create(GitHubReleaseClient::class),
    GitHubReleaseWebhookVerifier::class => create(GitHubReleaseWebhookVerifier::class),
    SystemUpdateVersionMatcher::class => create(SystemUpdateVersionMatcher::class),
    SystemDeployService::class => create(SystemDeployService::class)
        ->constructor(get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class)),
    SystemDeployTriggerService::class => create(SystemDeployTriggerService::class)
        ->constructor(
            get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class),
            get(SystemDeployService::class),
            get(\PaginiumCMS\Core\Scheduler\Services\JobRegistryStore::class),
            get(\PaginiumCMS\Core\Scheduler\Services\JobQueueStore::class),
            get(\PaginiumCMS\Core\Scheduler\Services\JobWorker::class),
            get(\PaginiumCMS\Core\Scheduler\Services\JobRunStore::class),
            get(\PaginiumCMS\Modules\Security\Services\SecurityAuditStore::class)
        ),
    SystemUpdateWebhookService::class => create(SystemUpdateWebhookService::class)
        ->constructor(
            get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class),
            get(GitHubReleaseWebhookVerifier::class),
            get(SystemDeployTriggerService::class)
        ),
    SystemDeployHandler::class => create(SystemDeployHandler::class)
        ->constructor(get(SystemDeployService::class)),
    SystemDeployCommand::class => create(SystemDeployCommand::class)
        ->constructor(get(SystemDeployService::class)),
    SystemUpdateController::class => create(SystemUpdateController::class)
        ->constructor(
            get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class),
            get(GitRepositoryInspector::class),
            get(GitHubReleaseClient::class),
            get(SystemDeployTriggerService::class),
            get(\PaginiumCMS\Core\Scheduler\Services\JobRegistryStore::class),
            get(\PaginiumCMS\Core\Scheduler\Services\JobRunStore::class),
            get(SystemUpdateWebhookService::class),
            get(SystemUpdateVersionMatcher::class),
            get(\PaginiumCMS\Http\Support\JsonResponder::class)
        ),
    GitHubReleaseWebhookController::class => create(GitHubReleaseWebhookController::class)
        ->constructor(
            get(SystemUpdateWebhookService::class),
            get(\PaginiumCMS\Http\Support\JsonResponder::class)
        ),
];
