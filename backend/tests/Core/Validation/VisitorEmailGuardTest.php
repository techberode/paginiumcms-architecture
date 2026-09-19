<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Validation;

use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Validation\VisitorEmailGuard;
use PaginiumCMS\Modules\Comments\Services\DisposableEmailDomainList;
use PHPUnit\Framework\TestCase;

final class VisitorEmailGuardTest extends TestCase
{
    private VisitorEmailGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();
        DisposableEmailDomainList::resetCacheForTesting();
        $this->guard = new VisitorEmailGuard(new DisposableEmailDomainList());
    }

    public function testNormalizesLastingMailbox(): void
    {
        $this->assertSame('reader@example.com', $this->guard->normalize(' Reader@Example.com '));
    }

    public function testRejectsEmptyAndDisposable(): void
    {
        try {
            $this->guard->normalize('');
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('email', $exception->getErrors());
        }

        try {
            $this->guard->normalize('bot@mailinator.com');
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('email', $exception->getErrors());
        }
    }
}
