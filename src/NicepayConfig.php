<?php

namespace Aliziodev\PayIdNicepay;

use InvalidArgumentException;

final class NicepayConfig
{
    /**
     * @param  array<string, string>  $paths
     */
    public function __construct(
        public readonly string $environment,
        public readonly string $merchantId,
        public readonly ?string $clientSecret,
        public readonly ?string $privateKey,
        public readonly ?string $merchantKey,
        public readonly ?string $partnerId,
        public readonly string $baseUrl,
        public readonly int $timeout,
        public readonly bool $webhookVerificationEnabled,
        public readonly ?string $webhookToken,
        public readonly ?string $webhookPublicKey,
        public readonly array $paths,
        public readonly string $paymentPath,
        public readonly string $statusPath,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(array $config): self
    {
        $merchantId = (string) ($config['merchant_id'] ?? '');

        if ($merchantId === '') {
            throw new InvalidArgumentException('NICEPAY_MERCHANT_ID is required.');
        }

        $environment = (string) ($config['environment'] ?? 'sandbox');

        return new self(
            environment: $environment,
            merchantId: $merchantId,
            clientSecret: isset($config['client_secret']) ? (string) $config['client_secret'] : null,
            privateKey: isset($config['private_key']) ? (string) $config['private_key'] : null,
            merchantKey: isset($config['merchant_key']) ? (string) $config['merchant_key'] : null,
            partnerId: isset($config['partner_id']) ? (string) $config['partner_id'] : null,
            baseUrl: rtrim((string) ($config['base_url'] ?? self::resolveBaseUrl($environment)), '/'),
            timeout: max(5, (int) ($config['timeout'] ?? 30)),
            webhookVerificationEnabled: (bool) ($config['webhook_verification_enabled'] ?? false),
            webhookToken: isset($config['webhook_token']) ? (string) $config['webhook_token'] : null,
            webhookPublicKey: isset($config['webhook_public_key']) ? (string) $config['webhook_public_key'] : null,
            paths: self::defaultPaths($config),
            paymentPath: (string) ($config['payment_path'] ?? '/api/v1.0/debit/payment-host-to-host'),
            statusPath: (string) ($config['status_path'] ?? '/api/v1.0/debit/status'),
        );
    }

    public function path(string $key): string
    {
        return $this->paths[$key] ?? throw new InvalidArgumentException("Unknown Nicepay path key [{$key}].");
    }

    private static function resolveBaseUrl(string $environment): string
    {
        return strtolower($environment) === 'production'
            ? 'https://www.nicepay.co.id'
            : 'https://dev.nicepay.co.id';
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, string>
     */
    private static function defaultPaths(array $config): array
    {
        return [
            'snap.access_token' => (string) ($config['snap_access_token_path'] ?? '/nicepay/api/v1.0/access-token/b2b'),
            'snap.va.generate' => (string) ($config['snap_va_generate_path'] ?? '/nicepay/api/v1.0/transfer-va/create-va'),
            'snap.va.inquiry' => (string) ($config['snap_va_inquiry_path'] ?? '/nicepay/api/v1.0/transfer-va/inquiry'),
            'snap.va.cancel' => (string) ($config['snap_va_cancel_path'] ?? '/nicepay/api/v1.0/transfer-va/cancel'),
            'snap.ewallet.payment' => (string) ($config['snap_ewallet_payment_path'] ?? '/nicepay/api/v1.0/ewallet/payment'),
            'snap.ewallet.inquiry' => (string) ($config['snap_ewallet_inquiry_path'] ?? '/nicepay/api/v1.0/ewallet/inquiry'),
            'snap.ewallet.refund' => (string) ($config['snap_ewallet_refund_path'] ?? '/nicepay/api/v1.0/ewallet/refund'),
            'snap.qris.generate' => (string) ($config['snap_qris_generate_path'] ?? '/nicepay/api/v1.0/qr/qr-mpm-generate'),
            'snap.qris.inquiry' => (string) ($config['snap_qris_inquiry_path'] ?? '/nicepay/api/v1.0/qr/qr-mpm-query'),
            'snap.qris.refund' => (string) ($config['snap_qris_refund_path'] ?? '/nicepay/api/v1.0/qr/qr-mpm-refund'),
            'snap.payout.registration' => (string) ($config['snap_payout_registration_path'] ?? '/nicepay/api/v1.0/payouts/register'),
            'snap.payout.approve' => (string) ($config['snap_payout_approve_path'] ?? '/nicepay/api/v1.0/payouts/approve'),
            'snap.payout.inquiry' => (string) ($config['snap_payout_inquiry_path'] ?? '/nicepay/api/v1.0/payouts/status'),
            'snap.payout.balance' => (string) ($config['snap_payout_balance_path'] ?? '/nicepay/api/v1.0/payouts/balance-inquiry'),
            'snap.payout.cancel' => (string) ($config['snap_payout_cancel_path'] ?? '/nicepay/api/v1.0/payouts/cancel'),
            'snap.payout.reject' => (string) ($config['snap_payout_reject_path'] ?? '/nicepay/api/v1.0/payouts/reject'),
            'v2.va.registration' => (string) ($config['v2_va_registration_path'] ?? '/nicepay/api/v2/va/registration'),
            'v2.va.inquiry' => (string) ($config['v2_va_inquiry_path'] ?? '/nicepay/api/v2/va/inquiry'),
            'v2.va.cancel' => (string) ($config['v2_va_cancel_path'] ?? '/nicepay/api/v2/va/cancel'),
            'v2.cvs.registration' => (string) ($config['v2_cvs_registration_path'] ?? '/nicepay/api/v2/cvs/registration'),
            'v2.cvs.inquiry' => (string) ($config['v2_cvs_inquiry_path'] ?? '/nicepay/api/v2/cvs/inquiry'),
            'v2.cvs.cancel' => (string) ($config['v2_cvs_cancel_path'] ?? '/nicepay/api/v2/cvs/cancel'),
            'v2.payloan.registration' => (string) ($config['v2_payloan_registration_path'] ?? '/nicepay/api/v2/payloan/registration'),
            'v2.payloan.inquiry' => (string) ($config['v2_payloan_inquiry_path'] ?? '/nicepay/api/v2/payloan/inquiry'),
            'v2.payloan.cancel' => (string) ($config['v2_payloan_cancel_path'] ?? '/nicepay/api/v2/payloan/cancel'),
            'v2.qris.registration' => (string) ($config['v2_qris_registration_path'] ?? '/nicepay/api/v2/qris/registration'),
            'v2.qris.inquiry' => (string) ($config['v2_qris_inquiry_path'] ?? '/nicepay/api/v2/qris/inquiry'),
            'v2.qris.cancel' => (string) ($config['v2_qris_cancel_path'] ?? '/nicepay/api/v2/qris/cancel'),
            'v2.card.registration' => (string) ($config['v2_card_registration_path'] ?? '/nicepay/api/v2/card/registration'),
            'v2.card.inquiry' => (string) ($config['v2_card_inquiry_path'] ?? '/nicepay/api/v2/card/inquiry'),
            'v2.card.cancel' => (string) ($config['v2_card_cancel_path'] ?? '/nicepay/api/v2/card/cancel'),
            'v2.card.payment' => (string) ($config['v2_card_payment_path'] ?? '/nicepay/api/v2/card/payment'),
            'v2.ewallet.registration' => (string) ($config['v2_ewallet_registration_path'] ?? '/nicepay/api/v2/ewallet/registration'),
            'v2.ewallet.inquiry' => (string) ($config['v2_ewallet_inquiry_path'] ?? '/nicepay/api/v2/ewallet/inquiry'),
            'v2.ewallet.cancel' => (string) ($config['v2_ewallet_cancel_path'] ?? '/nicepay/api/v2/ewallet/cancel'),
            'v2.ewallet.payment' => (string) ($config['v2_ewallet_payment_path'] ?? '/nicepay/api/v2/ewallet/payment'),
        ];
    }
}
