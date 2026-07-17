<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Http\Controllers\Admin\UserController;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Contracts\PasswordPolicyInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\PasswordPolicy;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * Testy admin UserController (Iterácia 5).
 */
class UserControllerTest extends TestCase
{
    private string $baseDir;
    private UserController $controller;
    private UserRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/pag_users_ctrl_' . uniqid();
        mkdir($this->baseDir . '/data/users', 0777, true);

        $validator = new FileValidator($this->baseDir . '/data');
        $this->repo = new UserRepository(new FileReader($validator), new FileWriter($validator), 'users');
        $this->controller = new UserController($this->repo, new Validator(), new PasswordPolicy(), new JsonResponder());
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testIndexListsUsers(): void
    {
        $user = new User();
        $user->setEmail('admin@test.sk');
        $user->setName('Admin');
        $user->setPassword('SecurePass1!');
        $user->setRoles(['ADMIN']);
        $this->repo->save($user);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/admin/users');
        $response = $this->controller->index($request, (new ResponseFactory())->createResponse());

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertTrue($body['success']);
        $this->assertCount(1, $body['data']['users']);
    }

    public function testStoreCreatesUser(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/admin/users')
            ->withBody($this->streamJson([
                'email' => 'new@test.sk',
                'name' => 'Nový',
                'role' => 'EDITOR',
                'password' => 'SecurePass1!',
            ]));

        $response = $this->controller->store($request, (new ResponseFactory())->createResponse());

        $this->assertSame(201, $response->getStatusCode());
        $this->assertNotNull($this->repo->findByEmail('new@test.sk'));
    }

    public function testDestroyPreventsSelfDelete(): void
    {
        $actor = new User();
        $actor->setEmail('me@test.sk');
        $actor->setName('Ja');
        $actor->setPassword('SecurePass1!');
        $actor->setRoles(['ADMIN']);
        $this->repo->save($actor);

        $request = (new ServerRequestFactory())
            ->createServerRequest('DELETE', '/api/admin/users/' . $actor->getId())
            ->withAttribute('user', $actor);

        $response = $this->controller->destroy(
            $request,
            (new ResponseFactory())->createResponse(),
            ['id' => $actor->getId()]
        );

        $this->assertSame(400, $response->getStatusCode());
    }

    /**
     * @param array<int|string, mixed> $data
     */
    private function streamJson(array $data): \Psr\Http\Message\StreamInterface
    {
        $stream = fopen('php://memory', 'r+');
        if ($stream === false) {
            self::fail('Unable to open memory stream.');
        }
        fwrite($stream, (string) json_encode($data));
        rewind($stream);

        return new \Slim\Psr7\Stream($stream);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function decode(\Psr\Http\Message\ResponseInterface $response): array
    {
        $response->getBody()->rewind();
        $decoded = json_decode((string) $response->getBody(), true);
        self::assertIsArray($decoded);

        return $decoded;
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
