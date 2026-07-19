<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Security\TwoFactorPolicy;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Validation\ValidationRules;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Http\Support\BulkBatchResult;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Modules\Security\Contracts\PasswordPolicyInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * === Controller: UserController (Admin) ===
 * CRUD správa používateľov a rolí (Iterácia 5, UI refresh 2.0.18).
 */
final class UserController
{
    /** @var list<string> */
    private const ALLOWED_ROLES = [
        AuthorizationInterface::ROLE_USER,
        AuthorizationInterface::ROLE_EDITOR,
        AuthorizationInterface::ROLE_ADMIN,
        AuthorizationInterface::ROLE_SUPER_ADMIN,
    ];

    /** @var list<string> */
    private const STAFF_ROLES = [
        AuthorizationInterface::ROLE_EDITOR,
        AuthorizationInterface::ROLE_ADMIN,
        AuthorizationInterface::ROLE_SUPER_ADMIN,
    ];

    public function __construct(
        private UserRepository $users,
        private SettingsRepositoryInterface $settings,
        private Validator $validator,
        private PasswordPolicyInterface $passwordPolicy,
        private JsonResponder $json
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $list = array_map(
            static fn (User $user): array => $user->jsonSerialize(),
            $this->users->findAll()
        );

        return $this->json->success($response, [
            'users' => $list,
            'meta' => [
                'require_two_factor_staff' => $this->requireTwoFactorStaff(),
            ],
        ]);
    }

    /**
     * @param array<string, string> $args
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $user = $this->resolveUser((string) ($args['id'] ?? ''));
        if ($user === null) {
            return $this->json->error($response, 'Používateľ neexistuje', 404);
        }

        return $this->json->success($response, [
            'user' => $user->toAdminDetail(true),
            'meta' => [
                'two_factor_enforced' => $this->isTwoFactorEnforcedFor($user),
                'require_two_factor_staff' => $this->requireTwoFactorStaff(),
            ],
        ]);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = $this->parseJsonBody($request);
        $rules = ValidationRules::for('user');
        if ($rules === null) {
            throw new RuntimeException('Chýbajú validačné pravidlá pre user.');
        }

        $validated = $this->validator->validate($payload, $rules['rules']);
        $this->assertValidRole((string) $validated['role']);

        if ($this->users->existsByEmail($validated['email'])) {
            return $this->json->error($response, 'E-mail už existuje', 409);
        }

        $username = strtolower(trim((string) ($validated['username'] ?? '')));
        if ($this->users->existsByUsername($username)) {
            return $this->json->error($response, 'Používateľské meno už existuje', 409);
        }

        $password = (string) ($payload['password'] ?? '');
        if ($password === '') {
            throw new ValidationException(['password' => ['Heslo je povinné pri vytváraní používateľa.']]);
        }

        $policyErrors = ValidationRules::validatePasswordPolicy($password);
        if ($policyErrors !== []) {
            throw new ValidationException(['password' => $policyErrors]);
        }

        $this->passwordPolicy->requireValid($password);

        $user = new User();
        $user->setEmail($validated['email']);
        $user->setUsername($username);
        $user->setName($validated['name']);
        $user->setRoles([(string) $validated['role']]);
        $user->setActive((bool) ($payload['active'] ?? true));
        $user->setPassword($password);

        if ($this->isTwoFactorEnforcedFor($user)) {
            // Vynútené 2FA ≠ automaticky twoFactorEnabled — secret vznikne až pri /account/security.
        } elseif (isset($payload['twoFactorEnabled'])) {
            $user->setTwoFactorEnabled((bool) $payload['twoFactorEnabled']);
        }

        $user->setUpdatedAt(time());

        $this->users->save($user);

        return $this->json->success(
            $response,
            ['user' => $user->toAdminDetail(true)],
            201,
            'Používateľ vytvorený'
        );
    }

    /**
     * @param array<string, string> $args
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $user = $this->resolveUser((string) ($args['id'] ?? ''));
        if ($user === null) {
            return $this->json->error($response, 'Používateľ neexistuje', 404);
        }
        $payload = $this->parseJsonBody($request);

        $rules = [
            'email' => ['email', 'max:255'],
            'username' => ['string', 'min:2', 'max:64', 'slug'],
            'name' => ['string', 'min:2', 'max:120'],
            'role' => ['in:USER,EDITOR,ADMIN,SUPER_ADMIN'],
            'active' => ['bool'],
        ];

        $validated = $this->validator->validate(
            array_intersect_key($payload, array_flip(['email', 'username', 'name', 'role', 'active'])),
            $rules
        );

        if (isset($validated['email']) && $validated['email'] !== $user->getEmail()) {
            if ($this->users->existsByEmail($validated['email'])) {
                return $this->json->error($response, 'E-mail už existuje', 409);
            }
            $user->setEmail($validated['email']);
        }

        if (isset($validated['username'])) {
            $username = strtolower(trim((string) $validated['username']));
            if ($this->users->existsByUsername($username, $user->getId())) {
                return $this->json->error($response, 'Používateľské meno už existuje', 409);
            }
            $user->setUsername($username);
        }

        if (isset($validated['name'])) {
            $user->setName($validated['name']);
        }

        if (isset($validated['role'])) {
            $this->assertValidRole((string) $validated['role']);
            $user->setRoles([(string) $validated['role']]);
        }

        if (array_key_exists('active', $validated)) {
            $user->setActive((bool) $validated['active']);
        }

        if ($this->isTwoFactorEnforcedFor($user)) {
            if (isset($payload['twoFactorEnabled']) && !(bool) $payload['twoFactorEnabled']) {
                // Staff nemôže vypnúť 2FA, ak je už aktivovaná — ignoruj pokus o disable.
            }
        } elseif (isset($payload['twoFactorEnabled'])) {
            $user->setTwoFactorEnabled((bool) $payload['twoFactorEnabled']);
        }

        if (isset($payload['password']) && $payload['password'] !== '') {
            $password = (string) $payload['password'];
            $policyErrors = ValidationRules::validatePasswordPolicy($password);
            if ($policyErrors !== []) {
                throw new ValidationException(['password' => $policyErrors]);
            }
            $this->passwordPolicy->requireValid($password);
            $user->setPassword($password);
        }

        $user->setUpdatedAt(time());
        $this->users->save($user);

        return $this->json->success(
            $response,
            ['user' => $user->toAdminDetail(true)],
            200,
            'Používateľ aktualizovaný'
        );
    }

    /**
     * @param array<string, string> $args
     */
    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $targetId = (string) ($args['id'] ?? '');
        $actor = $request->getAttribute('user');

        if (!$actor instanceof User) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        if ($actor->getId() === $targetId) {
            return $this->json->error($response, 'Nemôžete zmazať vlastný účet', 400);
        }

        $target = $this->resolveUser($targetId);
        if ($target === null) {
            return $this->json->error($response, 'Používateľ neexistuje', 404);
        }

        if ($target->isSuperAdmin() && $this->countSuperAdmins() <= 1) {
            return $this->json->error($response, 'Nemôžete zmazať posledného super administrátora', 400);
        }

        $this->users->delete($targetId);

        return $this->json->success($response, null, 200, 'Používateľ zmazaný');
    }

    public function bulkDestroy(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->parseJsonBody($request);
        $ids = isset($data['ids']) && is_array($data['ids'])
            ? array_values(array_filter(
                array_map(static fn ($id): string => is_string($id) ? trim($id) : '', $data['ids']),
                static fn (string $id): bool => $id !== ''
            ))
            : [];

        if ($ids === []) {
            return $this->json->error($response, 'Vyžaduje sa aspoň jedno ID', 400);
        }

        $actor = $request->getAttribute('user');
        if (!$actor instanceof User) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        $batch = new BulkBatchResult();
        foreach ($ids as $targetId) {
            if ($actor->getId() === $targetId) {
                $batch->addFailure($targetId, 'Nemôžete zmazať vlastný účet');

                continue;
            }

            $target = $this->resolveUser($targetId);
            if ($target === null) {
                $batch->addFailure($targetId, 'Používateľ neexistuje');

                continue;
            }

            if ($target->isSuperAdmin() && $this->countSuperAdmins() <= 1) {
                $batch->addFailure($targetId, 'Nemôžete zmazať posledného super administrátora');

                continue;
            }

            $this->users->delete($targetId);
            $batch->addSuccess($targetId);
        }

        return $this->json->success($response, $batch->toArray(), 200, 'Hromadné mazanie dokončené');
    }

    private function resolveUser(string $id): ?User
    {
        if ($id === '') {
            return null;
        }

        return $this->users->findById($id);
    }

    private function assertValidRole(string $role): void
    {
        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            throw new ValidationException(['role' => ['Neprípustná rola.']]);
        }
    }

    private function requireTwoFactorStaff(): bool
    {
        if (!TwoFactorPolicy::isRequired()) {
            return false;
        }

        $security = $this->settings->group('security');

        return (bool) ($security['requireTwoFactorStaff'] ?? true);
    }

    private function isTwoFactorEnforcedFor(User $user): bool
    {
        if (!$this->requireTwoFactorStaff()) {
            return false;
        }

        foreach (self::STAFF_ROLES as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    private function countSuperAdmins(): int
    {
        $count = 0;
        foreach ($this->users->findAll() as $user) {
            if ($user->isSuperAdmin()) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function parseJsonBody(ServerRequestInterface $request): array
    {
        $data = json_decode((string) $request->getBody(), true);

        return is_array($data) ? $data : [];
    }
}
