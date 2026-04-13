<?php

use Aliziodev\PayId\Exceptions\ProviderApiException;
use Aliziodev\PayIdNicepay\Http\NicepayClient;
use Aliziodev\PayIdNicepay\NicepayConfig;
use Illuminate\Support\Facades\Http;

it('client sends request for charge and status', function () {
    Http::fake([
        'https://dev.nicepay.co.id/*' => Http::response([
            'referenceNo' => 'ORDER-1',
            'transactionStatus' => 'PENDING',
        ], 200),
    ]);

    $client = new NicepayClient(NicepayConfig::fromArray(['merchant_id' => 'IONPAYTEST']));

    $charge = $client->charge(['referenceNo' => 'ORDER-1']);
    $status = $client->status(['referenceNo' => 'ORDER-1']);

    expect($charge['referenceNo'])->toBe('ORDER-1')
        ->and($status['transactionStatus'])->toBe('PENDING');

    Http::assertSent(fn ($request) => $request->hasHeader('X-MERCHANT-ID') && $request->hasHeader('X-SIGNATURE'));
});

it('client supports representative snap and v2 endpoints', function () {
    Http::fake([
        'https://dev.nicepay.co.id/*' => Http::response(['ok' => true], 200),
    ]);

    $client = new NicepayClient(NicepayConfig::fromArray(['merchant_id' => 'IONPAYTEST']));

    expect($client->snapAccessToken([])['ok'])->toBeTrue()
        ->and($client->snapVaGenerate([])['ok'])->toBeTrue()
        ->and($client->snapQrisInquiry([])['ok'])->toBeTrue()
        ->and($client->v2VaRegistration([])['ok'])->toBeTrue()
        ->and($client->v2CardPayment([])['ok'])->toBeTrue();
});

it('client throws provider api exception on failed response', function () {
    Http::fake([
        'https://dev.nicepay.co.id/*' => Http::response([
            'responseMessage' => 'Bad request',
        ], 400),
    ]);

    $client = new NicepayClient(NicepayConfig::fromArray(['merchant_id' => 'IONPAYTEST']));
    $client->charge(['referenceNo' => 'ORDER-FAIL']);
})->throws(ProviderApiException::class);
