<?php

use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\Enums\PaymentChannel;
use Aliziodev\PayId\Enums\PaymentStatus;
use Aliziodev\PayId\Managers\PayIdManager;
use Illuminate\Support\Facades\Http;

it('manager can resolve nicepay and execute charge plus status', function () {
    Http::fake([
        'https://dev.nicepay.co.id/api/v1.0/debit/payment-host-to-host' => Http::response([
            'tXid' => 'TX-MGR-001',
            'referenceNo' => 'ORDER-MGR-001',
            'transactionStatus' => 'SUCCESS',
        ], 200),
        'https://dev.nicepay.co.id/api/v1.0/debit/status' => Http::response([
            'tXid' => 'TX-MGR-001',
            'referenceNo' => 'ORDER-MGR-001',
            'transactionStatus' => 'PENDING',
            'amt' => 100000,
            'currency' => 'IDR',
        ], 200),
    ]);

    /** @var PayIdManager $manager */
    $manager = app(PayIdManager::class);

    $charge = $manager->driver('nicepay')->charge(ChargeRequest::make([
        'merchant_order_id' => 'ORDER-MGR-001',
        'amount' => 100000,
        'currency' => 'IDR',
        'channel' => PaymentChannel::Qris,
        'customer' => [
            'name' => 'Budi',
            'email' => 'budi@example.com',
        ],
    ]));

    $status = $manager->driver('nicepay')->status('ORDER-MGR-001');

    expect($charge->providerName)->toBe('nicepay')
        ->and($charge->status)->toBe(PaymentStatus::Paid)
        ->and($status->status)->toBe(PaymentStatus::Pending)
        ->and($status->amount)->toBe(100000);
});
