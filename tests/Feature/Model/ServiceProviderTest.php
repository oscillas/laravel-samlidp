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
}
