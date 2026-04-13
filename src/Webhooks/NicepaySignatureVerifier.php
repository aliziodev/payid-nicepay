<?php

namespace Aliziodev\PayIdNicepay\Webhooks;

use Aliziodev\PayIdNicepay\NicepayConfig;
use Illuminate\Http\Request;

final class NicepaySignatureVerifier
{
    public function __construct(
        protected readonly NicepayConfig $config,
    ) {}

    public function verify(Request $request): bool
    {
        if (! $this->config->webhookVerificationEnabled) {
            return true;
        }

        $token = trim((string) $this->config->webhookToken);
        $incomingToken = (string) ($request->header('x-callback-token') ?? $request->header('X-CALLBACK-TOKEN') ?? '');

        if ($token !== '' && $incomingToken !== '') {
            return hash_equals($token, $incomingToken);
        }

        $publicKey = trim((string) $this->config->webhookPublicKey);
        $signature = (string) ($request->header('x-signature') ?? $request->header('X-SIGNATURE') ?? '');

        if ($publicKey === '' || $signature === '') {
            return false;
        }

        $decoded = base64_decode($signature, true);

        if (! is_string($decoded)) {
            return false;
        }

        $verified = openssl_verify(
            (string) $request->getContent(),
            $decoded,
            $publicKey,
            OPENSSL_ALGO_SHA256,
        );

        return $verified === 1;
    }
}
