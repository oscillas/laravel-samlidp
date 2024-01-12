<?php

declare(strict_types=1);

namespace CodeGreenCreative\SamlIdp\Tests\Feature\Model;

use CodeGreenCreative\SamlIdp\Models\ServiceProvider;
use CodeGreenCreative\SamlIdp\Tests\TestCase;
use Illuminate\Database\QueryException;

class ServiceProviderTest extends TestCase
{
    /** @test */
    public function certificate_is_optional(): void
    {
        try {
            factory(ServiceProvider::class)->create([
                'certificate' => null,
                'encrypt_assertion' => false,
            ]);
        } catch (\Exception $e) {
            this->fail("Create should not have failed: {$e->getMessage()}");
        }

        $this->expectNotToPerformAssertions();
    }

    /** @test */
    public function certificate_required_if_encrypt_assertions_is_true(): void
    {
        try {
            factory(ServiceProvider::class)->create([
                'certificate' => null,
                'encrypt_assertion' => true,
            ]);
        } catch (\Exception $e) {
            if (get_class($e) === QueryException::class && $e->getCode() === '23514') {
                $this->expectNotToPerformAssertions();
                return;
            }

            $this->fail("Unexpected error: {$e->getMessage()}");
        }

        $this->fail('Create should have failed since encrypt_assertion is true and no certificate was provided');
    }

    /** @test */
    public function certificates_can_be_long(): void
    {
        try {
            factory(ServiceProvider::class)->create([
                'certificate' => str_repeat('a', 65536 - 1),
            ]);
        } catch (\Exception $e) {
            $this->fail("Create should not have failed: {$e->getMessage()}");
        }

        $this->expectNotToPerformAssertions();
    }
}
