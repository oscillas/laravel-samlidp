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
        } catch (QueryException $qe) {
            if ($qe->getCode() === '23502') {
                $this->fail("Certificate should be optional: {$qe->getMessage()}");
            }

            throw $qe;
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
        } catch (QueryException $qe) {
            if ($qe->getCode() === '23514') {
                $this->expectNotToPerformAssertions();
                return;
            }

            $this->fail("Unexpected error: {$qe->getMessage()}");
        }

        $this->fail('Create should have failed since encrypt_assertion is true and no certificate was provided');
    }
}
