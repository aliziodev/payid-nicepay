<?php

namespace Aliziodev\PayIdNicepay;

use Aliziodev\PayId\Factories\DriverFactory;
use Aliziodev\PayIdNicepay\Http\NicepayClient;
use Aliziodev\PayIdNicepay\Webhooks\NicepaySignatureVerifier;
use Aliziodev\PayIdNicepay\Webhooks\NicepayWebhookParser;
use Illuminate\Support\ServiceProvider;

class NicepayServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->resolving(DriverFactory::class, function (DriverFactory $factory): void {
            $factory->extend('nicepay', function (array $config): NicepayDriver {
                $baseConfig = (array) config('payid.drivers.nicepay', []);
                $nicepayConfig = NicepayConfig::fromArray(array_merge($baseConfig, $config));

                return new NicepayDriver(
                    config: $nicepayConfig,
                    client: new NicepayClient($nicepayConfig),
                    signatureVerifier: new NicepaySignatureVerifier($nicepayConfig),
                    webhookParser: new NicepayWebhookParser,
                );
            });
        });
    }
}
