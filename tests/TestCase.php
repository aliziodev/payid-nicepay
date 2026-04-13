<?php

namespace Aliziodev\PayIdNicepay\Tests;

use Aliziodev\PayId\PayIdServiceProvider;
use Aliziodev\PayIdNicepay\NicepayServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            PayIdServiceProvider::class,
            NicepayServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('payid.default', 'nicepay');

        $app['config']->set('payid.drivers.nicepay', [
            'driver' => 'nicepay',
            'environment' => 'sandbox',
            'merchant_id' => 'IONPAYTEST',
            'client_secret' => 'test-client-secret',
            'private_key' => 'test-private-key',
            'merchant_key' => 'test-merchant-key',
            'partner_id' => 'IONPAYTEST',
            'webhook_verification_enabled' => false,
            'webhook_token' => 'nicepay-webhook-token',
            'webhook_public_key' => null,
        ]);
    }
}
