<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Validation;

use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Validation\Validator;
use PHPUnit\Framework\TestCase;

/**
 * Testy zdieľaného validátora (Iterácia 4).
 * Musí byť zhodný s frontendovým zrkadlom frontend/src/utils/validation.ts.
 */
class ValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new Validator();
    }

    public function testPassesAndCoercesTypes(): void
    {
        $result = $this->validator->validate(
            ['name' => 'Ahoj', 'count' => '5', 'active' => 'true'],
            [
                'name' => ['required', 'string', 'min:2', 'max:10'],
                'count' => ['required', 'int', 'min:1', 'max:10'],
                'active' => ['bool'],
            ]
        );

        $this->assertSame('Ahoj', $result['name']);
        $this->assertSame(5, $result['count']);
        $this->assertTrue($result['active']);
    }

    public function testRequiredFails(): void
    {
        $errors = $this->collectErrors(['name' => ''], ['name' => ['required', 'string']]);

        $this->assertArrayHasKey('name', $errors);
    }

    public function testSkipsOptionalEmpty(): void
    {
        $result = $this->validator->validate(
            ['note' => ''],
            ['note' => ['string', 'max:10']]
        );

        $this->assertArrayNotHasKey('note', $result, 'Nepovinné prázdne pole sa má preskočiť');
    }

    public function testStringLengthBounds(): void
    {
        $tooShort = $this->collectErrors(['x' => 'a'], ['x' => ['string', 'min:2']]);
        $this->assertArrayHasKey('x', $tooShort);

        $tooLong = $this->collectErrors(['x' => 'abcdef'], ['x' => ['string', 'max:3']]);
        $this->assertArrayHasKey('x', $tooLong);
    }

    public function testNumericBounds(): void
    {
        $tooSmall = $this->collectErrors(['n' => '0'], ['n' => ['int', 'min:1']]);
        $this->assertArrayHasKey('n', $tooSmall);

        $tooBig = $this->collectErrors(['n' => '101'], ['n' => ['int', 'max:100']]);
        $this->assertArrayHasKey('n', $tooBig);
    }

    public function testEmailAndUrl(): void
    {
        $bad = $this->collectErrors(
            ['e' => 'not-an-email', 'u' => 'not a url'],
            ['e' => ['email'], 'u' => ['url']]
        );
        $this->assertArrayHasKey('e', $bad);
        $this->assertArrayHasKey('u', $bad);

        $ok = $this->validator->validate(
            ['e' => 'a@b.sk', 'u' => 'https://example.com'],
            ['e' => ['email'], 'u' => ['url']]
        );
        $this->assertSame('a@b.sk', $ok['e']);
    }

    public function testInRule(): void
    {
        $bad = $this->collectErrors(['lang' => 'de'], ['lang' => ['in:sk,en']]);
        $this->assertArrayHasKey('lang', $bad);

        $ok = $this->validator->validate(['lang' => 'sk'], ['lang' => ['in:sk,en']]);
        $this->assertSame('sk', $ok['lang']);
    }

    public function testTimezoneRule(): void
    {
        $bad = $this->collectErrors(['timezone' => 'Not/A/Zone'], ['timezone' => ['required', 'timezone']]);
        $this->assertArrayHasKey('timezone', $bad);

        $ok = $this->validator->validate(
            ['timezone' => 'Europe/Bratislava'],
            ['timezone' => ['required', 'timezone']]
        );
        $this->assertSame('Europe/Bratislava', $ok['timezone']);
    }

    public function testCollectsMultipleFieldErrors(): void
    {
        $errors = $this->collectErrors(
            ['a' => '', 'b' => 'x'],
            ['a' => ['required'], 'b' => ['int']]
        );

        $this->assertCount(2, $errors);
    }

    /**
     * @param array<int|string, mixed> $data
     * @param array<string, list<string>> $rules
     * @return array<string, list<string>>
     */
    private function collectErrors(array $data, array $rules): array
    {
        try {
            $this->validator->validate($data, $rules);
        } catch (ValidationException $e) {
            return $e->getErrors();
        }

        $this->fail('Očakával sa ValidationException');
    }
}
