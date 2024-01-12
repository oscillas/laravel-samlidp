<?php

declare(strict_types=1);

namespace CodeGreenCreative\SamlIdp\Tests\Feature\Model;

use CodeGreenCreative\SamlIdp\Models\ServiceProvider;
use CodeGreenCreative\SamlIdp\Tests\TestCase;
use Illuminate\Database\QueryException;
use LightSaml\SamlConstants;

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
            $this->fail("Create should not have failed: {$e->getMessage()}");
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

    /** @test */
    public function can_convert_self_to_samlidp_compatible_sp_config(): void
    {
        // Arrange
        $sp = factory(ServiceProvider::class)->create();

        // Act
        $config = $sp->toSpConfig();

        // Assert
        $this->assertIsArray($config);

        $this->assertEquals($sp->destination_url, $config['destination']);
        $this->assertEquals($sp->logout_url, $config['logout']);
        $this->assertEquals($sp->certificate, $config['certificate']);
        $this->assertEquals($sp->block_encryption_algorithm, $config['block_encryption_algorithm']);
        $this->assertEquals($sp->key_transport_encryption, $config['key_transport_encryption']);
        $this->assertEquals($sp->query_params, $config['query_params']);
        $this->assertEquals($sp->encrypt_assertion, $config['encrypt_assertion']);
        $this->assertEquals($sp->binding, $config['binding']);
    }

    /** @test */
    public function binding_is_nullable(): void
    {
        try {
            factory(ServiceProvider::class)->create([
                'binding' => null,
            ]);
        } catch (\Exception $e) {
            $this->fail("Create should not have failed: {$e->getMessage()}");
        }

        $this->expectNotToPerformAssertions();
    }

    /** @test */
    public function binding_is_a_string(): void
    {
        try {
            factory(ServiceProvider::class)->create([
                'binding' => SamlConstants::BINDING_SAML2_HTTP_POST,
            ]);
        } catch (\Exception $e) {
            $this->fail("Create should not have failed: {$e->getMessage()}");
        }

        $this->expectNotToPerformAssertions();
    }
}
