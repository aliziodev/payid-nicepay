<?php

use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\Enums\Capability;
use Aliziodev\PayId\Enums\PaymentChannel;
use Aliziodev\PayId\Enums\PaymentStatus;
use Aliziodev\PayIdNicepay\Http\NicepayClient;
use Aliziodev\PayIdNicepay\NicepayConfig;
use Aliziodev\PayIdNicepay\NicepayDriver;
use Aliziodev\PayIdNicepay\Webhooks\NicepaySignatureVerifier;
use Aliziodev\PayIdNicepay\Webhooks\NicepayWebhookParser;

it('driver exposes core capabilities', function () {
    $driver = new NicepayDriver(
        config: NicepayConfig::fromArray(['merchant_id' => 'IONPAYTEST']),
        client: Mockery::mock(NicepayClient::class),
        signatureVerifier: new NicepaySignatureVerifier(NicepayConfig::fromArray(['merchant_id' => 'IONPAYTEST'])),
        webhookParser: new NicepayWebhookParser,
    );

    expect($driver->supports(Capability::Charge))->toBeTrue()
        ->and($driver->supports(Capability::Status))->toBeTrue()
        ->and($driver->supports(Capability::WebhookVerification))->toBeTrue()
        ->and($driver->supports(Capability::WebhookParsing))->toBeTrue();
});

it('driver charge and status map correctly', function () {
    $client = Mockery::mock(NicepayClient::class);
    $client->shouldReceive('charge')->once()->andReturn([
        'tXid' => 'TX-NICEPAY-001',
        'referenceNo' => 'ORDER-NICEPAY-001',
        'transactionStatus' => 'SUCCESS',
        'redirectUrl' => 'https://dev.nicepay.co.id/redirect/tx-1',
    ]);
    $client->shouldReceive('status')->once()->andReturn([
        'tXid' => 'TX-NICEPAY-001',
        'referenceNo' => 'ORDER-NICEPAY-001',
        'transactionStatus' => 'PENDING',
        'amt' => 100000,
        'currency' => 'IDR',
    ]);

    $driver = new NicepayDriver(
        config: NicepayConfig::fromArray(['merchant_id' => 'IONPAYTEST']),
        client: $client,
        signatureVerifier: new NicepaySignatureVerifier(NicepayConfig::fromArray(['merchant_id' => 'IONPAYTEST'])),
        webhookParser: new NicepayWebhookParser,
    );

    $charge = $driver->charge(ChargeRequest::make([
        'merchant_order_id' => 'ORDER-NICEPAY-001',
        'amount' => 100000,
        'currency' => 'IDR',
        'channel' => PaymentChannel::Qris,
        'customer' => ['name' => 'Budi', 'email' => 'budi@example.com'],
    ]));

    $status = $driver->status('ORDER-NICEPAY-001');

    expect($charge->status)->toBe(PaymentStatus::Paid)
        ->and($status->status)->toBe(PaymentStatus::Pending)
        ->and($status->amount)->toBe(100000);
});

it('driver exposes official sdk feature methods as extension APIs', function () {
    $client = Mockery::mock(NicepayClient::class);
    $client->shouldReceive('snapAccessToken')->once()->andReturn(['ok' => true]);
    $client->shouldReceive('snapVaGenerate')->once()->andReturn(['ok' => true]);
    $client->shouldReceive('snapEwalletRefund')->once()->andReturn(['ok' => true]);
    $client->shouldReceive('snapPayoutBalance')->once()->andReturn(['ok' => true]);
    $client->shouldReceive('v2CardPayment')->once()->andReturn(['ok' => true]);
    $client->shouldReceive('v2EwalletPayment')->once()->andReturn(['ok' => true]);

    $driver = new NicepayDriver(
        config: NicepayConfig::fromArray(['merchant_id' => 'IONPAYTEST']),
        client: $client,
        signatureVerifier: new NicepaySignatureVerifier(NicepayConfig::fromArray(['merchant_id' => 'IONPAYTEST'])),
        webhookParser: new NicepayWebhookParser,
    );

    expect($driver->snapAccessToken([])['ok'])->toBeTrue()
        ->and($driver->snapVaGenerate([])['ok'])->toBeTrue()
        ->and($driver->snapEwalletRefund([])['ok'])->toBeTrue()
        ->and($driver->snapPayoutBalance([])['ok'])->toBeTrue()
        ->and($driver->v2CardPayment([])['ok'])->toBeTrue()
        ->and($driver->v2EwalletPayment([])['ok'])->toBeTrue();
});
