<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Models\User;

/**
 * Opt-in public cards for Contact / Support (It.93o). Never dumps full user JSON.
 */
final class PublishedStaffDirectory
{
    public function __construct(
        private UserRepository $users,
    ) {
    }

    /**
     * @return array{contacts: list<array<string, mixed>>, support: list<array<string, mixed>>}
     */
    public function publicLists(): array
    {
        $contacts = [];
        $support = [];

        foreach ($this->users->findAll() as $user) {
            if (!$user instanceof User || !$user->isActive()) {
                continue;
            }

            $publish = $user->getPublish();
            if ($publish['contact'] === true) {
                $card = $this->present($user);
                if ($card !== null) {
                    $contacts[] = $card;
                }
            }
            if ($publish['support'] === true) {
                $card = $this->present($user);
                if ($card !== null) {
                    $support[] = $card;
                }
            }
        }

        return [
            'contacts' => $contacts,
            'support' => $support,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function present(User $user): ?array
    {
        $name = trim($user->getName());
        if ($name === '') {
            return null;
        }

        $publish = $user->getPublish();
        $card = [
            'id' => $user->getId(),
            'name' => $name,
            'jobTitle' => $user->getJobTitle(),
            'bio' => $user->getBio(),
            'avatarUrl' => $user->getAvatarUrl(),
        ];

        if ($publish['phone'] === true && $user->getPhone() !== '') {
            $card['phone'] = $user->getPhone();
        }
        if ($publish['email'] === true) {
            $card['email'] = $user->getEmail();
        }
        if ($publish['address'] === true) {
            $address = $user->getAddress();
            if (implode('', $address) !== '') {
                $card['address'] = $address;
            }
        }
        if ($publish['experience'] === true && $user->getExperience() !== []) {
            $card['experience'] = $user->getExperience();
        }
        if ($publish['education'] === true && $user->getEducation() !== []) {
            $card['education'] = $user->getEducation();
        }
        if ($publish['socials'] === true) {
            $socials = [];
            foreach ($user->getSocialAccounts() as $account) {
                $href = $account['directChat']
                    ? UserProfileFields::chatUrl($account['platform'], $account['url'])
                    : ($account['platform'] === 'email'
                        ? 'mailto:' . $account['url']
                        : $account['url']);
                if ($href === '') {
                    continue;
                }
                $socials[] = [
                    'platform' => $account['platform'],
                    'label' => $account['label'],
                    'url' => $href,
                    'directChat' => $account['directChat'],
                ];
            }
            if ($socials !== []) {
                $card['socials'] = $socials;
            }
        }

        return $card;
    }
}
