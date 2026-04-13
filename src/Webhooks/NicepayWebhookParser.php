<?php

namespace Aliziodev\PayIdNicepay\Webhooks;

use Aliziodev\PayId\DTO\NormalizedWebhook;
use Aliziodev\PayId\Enums\PaymentChannel;
use Aliziodev\PayId\Enums\PaymentStatus;
use Aliziodev\PayId\Exceptions\WebhookParsingException;
use Illuminate\Http\Request;

final class NicepayWebhookParser
{
    public function parse(Request $request, bool $signatureValid): NormalizedWebhook
    {
        $payload = $request->all();

        $merchantOrderId = (string) data_get($payload, 'referenceNo', data_get($payload, 'reference_no', data_get($payload, 'order_id', '')));

        if ($merchantOrderId === '') {
            throw new WebhookParsingException('nicepay', 'Missing referenceNo/reference_no/order_id in webhook payload.');
        }

        $statusRaw = (string) data_get($payload, 'transactionStatus', data_get($payload, 'status', 'PENDING'));

        return NormalizedWebhook::make([
            'provider' => 'nicepay',
            'merchant_order_id' => $merchantOrderId,
            'provider_transaction_id' => (string) data_get($payload, 'tXid', data_get($payload, 'txid', '')),
            'event_type' => (string) data_get($payload, 'eventType', 'payment.notification'),
            'status' => $this->mapStatus($statusRaw),
            'amount' => (int) data_get($payload, 'amount', data_get($payload, 'amt', 0)),
            'currency' => (string) data_get($payload, 'currency', 'IDR'),
            'channel' => $this->mapChannel((string) data_get($payload, 'payMethod', data_get($payload, 'paymentMethod', ''))),
            'signature_valid' => $signatureValid,
            'raw_payload' => $payload,
            'occurred_at' => data_get($payload, 'transDate', data_get($payload, 'created_at', now()->toISOString())),
        ]);
    }

    protected function mapStatus(string $status): PaymentStatus
    {
        return match (strtoupper($status)) {
            'SUCCESS', 'PAID', 'SETTLEMENT', 'CAPTURED' => PaymentStatus::Paid,
            'PENDING', 'UNPAID' => PaymentStatus::Pending,
            'EXPIRED' => PaymentStatus::Expired,
            'FAILED', 'DENY' => PaymentStatus::Failed,
            'CANCEL', 'CANCELLED', 'CANCELED' => PaymentStatus::Cancelled,
            'REFUND', 'REFUNDED' => PaymentStatus::Refunded,
            default => PaymentStatus::Created,
        };
    }

    protected function mapChannel(string $channel): ?PaymentChannel
    {
        return match (strtoupper($channel)) {
            '02', 'VA', 'VIRTUAL_ACCOUNT' => PaymentChannel::VaOther,
            '03', 'CVS', 'ALFAMART' => PaymentChannel::CstoreAlfamart,
            '04', 'CARD', 'CC', 'CREDIT_CARD' => PaymentChannel::CreditCard,
            '05', 'EWALLET', 'GOPAY' => PaymentChannel::Gopay,
            '06', 'QRIS' => PaymentChannel::Qris,
            default => null,
        };
    }
}
