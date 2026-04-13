<?php

namespace Aliziodev\PayIdNicepay;

use Aliziodev\PayId\Contracts\DriverInterface;
use Aliziodev\PayId\Contracts\HasCapabilities;
use Aliziodev\PayId\Contracts\SupportsCharge;
use Aliziodev\PayId\Contracts\SupportsStatus;
use Aliziodev\PayId\Contracts\SupportsWebhookParsing;
use Aliziodev\PayId\Contracts\SupportsWebhookVerification;
use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\DTO\ChargeResponse;
use Aliziodev\PayId\DTO\NormalizedWebhook;
use Aliziodev\PayId\DTO\StatusResponse;
use Aliziodev\PayId\Enums\Capability;
use Aliziodev\PayId\Enums\PaymentChannel;
use Aliziodev\PayId\Enums\PaymentStatus;
use Aliziodev\PayIdNicepay\Http\NicepayClient;
use Aliziodev\PayIdNicepay\Webhooks\NicepaySignatureVerifier;
use Aliziodev\PayIdNicepay\Webhooks\NicepayWebhookParser;
use Illuminate\Http\Request;

class NicepayDriver implements DriverInterface, SupportsCharge, SupportsStatus, SupportsWebhookParsing, SupportsWebhookVerification
{
    use HasCapabilities;

    public function __construct(
        protected readonly NicepayConfig $config,
        protected readonly NicepayClient $client,
        protected readonly NicepaySignatureVerifier $signatureVerifier,
        protected readonly NicepayWebhookParser $webhookParser,
    ) {}

    public function getName(): string
    {
        return 'nicepay';
    }

    public function getCapabilities(): array
    {
        return [
            Capability::Charge,
            Capability::Status,
            Capability::WebhookVerification,
            Capability::WebhookParsing,
        ];
    }

    public function charge(ChargeRequest $request): ChargeResponse
    {
        $payload = [
            'referenceNo' => $request->merchantOrderId,
            'amt' => $request->amount,
            'currency' => $request->currency,
            'goodsNm' => $request->description ?: 'PayID '.$request->merchantOrderId,
            'billingNm' => $request->customer->name,
            'billingEmail' => $request->customer->email,
            'billingPhone' => $request->customer->phone,
            'payMethod' => $this->mapChannel($request->channel),
            'callBackUrl' => $request->successUrl,
            'dbProcessUrl' => $request->callbackUrl,
            'metadata' => $request->metadata,
        ];

        $raw = $this->client->charge($payload);

        return ChargeResponse::make([
            'provider_name' => 'nicepay',
            'provider_transaction_id' => (string) data_get($raw, 'tXid', data_get($raw, 'txid', '')),
            'merchant_order_id' => (string) data_get($raw, 'referenceNo', $request->merchantOrderId),
            'status' => $this->mapStatus((string) data_get($raw, 'transactionStatus', data_get($raw, 'status', 'PENDING'))),
            'payment_url' => data_get($raw, 'redirectUrl', data_get($raw, 'paymentUrl')),
            'raw_response' => $raw,
        ]);
    }

    public function status(string $merchantOrderId): StatusResponse
    {
        $raw = $this->client->status(['referenceNo' => $merchantOrderId]);

        return StatusResponse::make([
            'provider_name' => 'nicepay',
            'provider_transaction_id' => (string) data_get($raw, 'tXid', data_get($raw, 'txid', '')),
            'merchant_order_id' => (string) data_get($raw, 'referenceNo', $merchantOrderId),
            'status' => $this->mapStatus((string) data_get($raw, 'transactionStatus', data_get($raw, 'status', 'PENDING'))),
            'raw_response' => $raw,
            'amount' => (int) data_get($raw, 'amount', data_get($raw, 'amt', 0)),
            'currency' => (string) data_get($raw, 'currency', 'IDR'),
        ]);
    }

    public function verifyWebhook(Request $request): bool
    {
        return $this->signatureVerifier->verify($request);
    }

    public function parseWebhook(Request $request): NormalizedWebhook
    {
        return $this->webhookParser->parse($request, $this->verifyWebhook($request));
    }

    // SNAP extension methods
    public function snapAccessToken(array $payload): array
    {
        return $this->client->snapAccessToken($payload);
    }

    public function snapVaGenerate(array $payload): array
    {
        return $this->client->snapVaGenerate($payload);
    }

    public function snapVaInquiry(array $payload): array
    {
        return $this->client->snapVaInquiry($payload);
    }

    public function snapVaCancel(array $payload): array
    {
        return $this->client->snapVaCancel($payload);
    }

    public function snapEwalletPayment(array $payload): array
    {
        return $this->client->snapEwalletPayment($payload);
    }

    public function snapEwalletInquiry(array $payload): array
    {
        return $this->client->snapEwalletInquiry($payload);
    }

    public function snapEwalletRefund(array $payload): array
    {
        return $this->client->snapEwalletRefund($payload);
    }

    public function snapQrisGenerate(array $payload): array
    {
        return $this->client->snapQrisGenerate($payload);
    }

    public function snapQrisInquiry(array $payload): array
    {
        return $this->client->snapQrisInquiry($payload);
    }

    public function snapQrisRefund(array $payload): array
    {
        return $this->client->snapQrisRefund($payload);
    }

    public function snapPayoutRegistration(array $payload): array
    {
        return $this->client->snapPayoutRegistration($payload);
    }

    public function snapPayoutApprove(array $payload): array
    {
        return $this->client->snapPayoutApprove($payload);
    }

    public function snapPayoutInquiry(array $payload): array
    {
        return $this->client->snapPayoutInquiry($payload);
    }

    public function snapPayoutBalance(array $payload = []): array
    {
        return $this->client->snapPayoutBalance($payload);
    }

    public function snapPayoutCancel(array $payload): array
    {
        return $this->client->snapPayoutCancel($payload);
    }

    public function snapPayoutReject(array $payload): array
    {
        return $this->client->snapPayoutReject($payload);
    }

    // V2 extension methods
    public function v2VaRegistration(array $payload): array
    {
        return $this->client->v2VaRegistration($payload);
    }

    public function v2VaInquiry(array $payload): array
    {
        return $this->client->v2VaInquiry($payload);
    }

    public function v2VaCancel(array $payload): array
    {
        return $this->client->v2VaCancel($payload);
    }

    public function v2CvsRegistration(array $payload): array
    {
        return $this->client->v2CvsRegistration($payload);
    }

    public function v2CvsInquiry(array $payload): array
    {
        return $this->client->v2CvsInquiry($payload);
    }

    public function v2CvsCancel(array $payload): array
    {
        return $this->client->v2CvsCancel($payload);
    }

    public function v2PayloanRegistration(array $payload): array
    {
        return $this->client->v2PayloanRegistration($payload);
    }

    public function v2PayloanInquiry(array $payload): array
    {
        return $this->client->v2PayloanInquiry($payload);
    }

    public function v2PayloanCancel(array $payload): array
    {
        return $this->client->v2PayloanCancel($payload);
    }

    public function v2QrisRegistration(array $payload): array
    {
        return $this->client->v2QrisRegistration($payload);
    }

    public function v2QrisInquiry(array $payload): array
    {
        return $this->client->v2QrisInquiry($payload);
    }

    public function v2QrisCancel(array $payload): array
    {
        return $this->client->v2QrisCancel($payload);
    }

    public function v2CardRegistration(array $payload): array
    {
        return $this->client->v2CardRegistration($payload);
    }

    public function v2CardInquiry(array $payload): array
    {
        return $this->client->v2CardInquiry($payload);
    }

    public function v2CardCancel(array $payload): array
    {
        return $this->client->v2CardCancel($payload);
    }

    public function v2CardPayment(array $payload): array
    {
        return $this->client->v2CardPayment($payload);
    }

    public function v2EwalletRegistration(array $payload): array
    {
        return $this->client->v2EwalletRegistration($payload);
    }

    public function v2EwalletInquiry(array $payload): array
    {
        return $this->client->v2EwalletInquiry($payload);
    }

    public function v2EwalletCancel(array $payload): array
    {
        return $this->client->v2EwalletCancel($payload);
    }

    public function v2EwalletPayment(array $payload): array
    {
        return $this->client->v2EwalletPayment($payload);
    }

    public function verifySignatureSha256(string $dataString, string $signatureBase64, string $publicKey): bool
    {
        $decoded = base64_decode($signatureBase64, true);

        if (! is_string($decoded)) {
            return false;
        }

        return openssl_verify($dataString, $decoded, $publicKey, OPENSSL_ALGO_SHA256) === 1;
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

    protected function mapChannel(PaymentChannel $channel): string
    {
        return match ($channel) {
            PaymentChannel::Qris => '06',
            PaymentChannel::CreditCard, PaymentChannel::DebitCard => '04',
            PaymentChannel::CstoreAlfamart, PaymentChannel::CstoreIndomaret => '03',
            PaymentChannel::Gopay, PaymentChannel::Ovo, PaymentChannel::Dana, PaymentChannel::Shopeepay => '05',
            default => '02',
        };
    }
}
