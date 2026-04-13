<?php

namespace Aliziodev\PayIdNicepay\Http;

use Aliziodev\PayId\Exceptions\ProviderApiException;
use Aliziodev\PayId\Exceptions\ProviderNetworkException;
use Aliziodev\PayIdNicepay\NicepayConfig;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class NicepayClient
{
    public function __construct(
        protected readonly NicepayConfig $config,
    ) {}

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    public function charge(array $payload): array
    {
        return $this->request('POST', $this->config->paymentPath, $payload);
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    public function status(array $payload): array
    {
        return $this->request('POST', $this->config->statusPath, $payload);
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    public function snapAccessToken(array $payload): array
    {
        return $this->callPath('snap.access_token', $payload);
    }

    public function snapVaGenerate(array $payload): array
    {
        return $this->callPath('snap.va.generate', $payload);
    }

    public function snapVaInquiry(array $payload): array
    {
        return $this->callPath('snap.va.inquiry', $payload);
    }

    public function snapVaCancel(array $payload): array
    {
        return $this->callPath('snap.va.cancel', $payload);
    }

    public function snapEwalletPayment(array $payload): array
    {
        return $this->callPath('snap.ewallet.payment', $payload);
    }

    public function snapEwalletInquiry(array $payload): array
    {
        return $this->callPath('snap.ewallet.inquiry', $payload);
    }

    public function snapEwalletRefund(array $payload): array
    {
        return $this->callPath('snap.ewallet.refund', $payload);
    }

    public function snapQrisGenerate(array $payload): array
    {
        return $this->callPath('snap.qris.generate', $payload);
    }

    public function snapQrisInquiry(array $payload): array
    {
        return $this->callPath('snap.qris.inquiry', $payload);
    }

    public function snapQrisRefund(array $payload): array
    {
        return $this->callPath('snap.qris.refund', $payload);
    }

    public function snapPayoutRegistration(array $payload): array
    {
        return $this->callPath('snap.payout.registration', $payload);
    }

    public function snapPayoutApprove(array $payload): array
    {
        return $this->callPath('snap.payout.approve', $payload);
    }

    public function snapPayoutInquiry(array $payload): array
    {
        return $this->callPath('snap.payout.inquiry', $payload);
    }

    public function snapPayoutBalance(array $payload = []): array
    {
        return $this->callPath('snap.payout.balance', $payload);
    }

    public function snapPayoutCancel(array $payload): array
    {
        return $this->callPath('snap.payout.cancel', $payload);
    }

    public function snapPayoutReject(array $payload): array
    {
        return $this->callPath('snap.payout.reject', $payload);
    }

    public function v2VaRegistration(array $payload): array
    {
        return $this->callPath('v2.va.registration', $payload);
    }

    public function v2VaInquiry(array $payload): array
    {
        return $this->callPath('v2.va.inquiry', $payload);
    }

    public function v2VaCancel(array $payload): array
    {
        return $this->callPath('v2.va.cancel', $payload);
    }

    public function v2CvsRegistration(array $payload): array
    {
        return $this->callPath('v2.cvs.registration', $payload);
    }

    public function v2CvsInquiry(array $payload): array
    {
        return $this->callPath('v2.cvs.inquiry', $payload);
    }

    public function v2CvsCancel(array $payload): array
    {
        return $this->callPath('v2.cvs.cancel', $payload);
    }

    public function v2PayloanRegistration(array $payload): array
    {
        return $this->callPath('v2.payloan.registration', $payload);
    }

    public function v2PayloanInquiry(array $payload): array
    {
        return $this->callPath('v2.payloan.inquiry', $payload);
    }

    public function v2PayloanCancel(array $payload): array
    {
        return $this->callPath('v2.payloan.cancel', $payload);
    }

    public function v2QrisRegistration(array $payload): array
    {
        return $this->callPath('v2.qris.registration', $payload);
    }

    public function v2QrisInquiry(array $payload): array
    {
        return $this->callPath('v2.qris.inquiry', $payload);
    }

    public function v2QrisCancel(array $payload): array
    {
        return $this->callPath('v2.qris.cancel', $payload);
    }

    public function v2CardRegistration(array $payload): array
    {
        return $this->callPath('v2.card.registration', $payload);
    }

    public function v2CardInquiry(array $payload): array
    {
        return $this->callPath('v2.card.inquiry', $payload);
    }

    public function v2CardCancel(array $payload): array
    {
        return $this->callPath('v2.card.cancel', $payload);
    }

    public function v2CardPayment(array $payload): array
    {
        return $this->callPath('v2.card.payment', $payload);
    }

    public function v2EwalletRegistration(array $payload): array
    {
        return $this->callPath('v2.ewallet.registration', $payload);
    }

    public function v2EwalletInquiry(array $payload): array
    {
        return $this->callPath('v2.ewallet.inquiry', $payload);
    }

    public function v2EwalletCancel(array $payload): array
    {
        return $this->callPath('v2.ewallet.cancel', $payload);
    }

    public function v2EwalletPayment(array $payload): array
    {
        return $this->callPath('v2.ewallet.payment', $payload);
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    protected function callPath(string $pathKey, array $payload = []): array
    {
        return $this->request('POST', $this->config->path($pathKey), $payload);
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    public function request(string $method, string $path, array $payload = []): array
    {
        $timestamp = now()->toIso8601String();
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);

        if (! is_string($body)) {
            $body = '{}';
        }

        $signature = hash('sha256', strtoupper($method).':'.$this->config->merchantId.':'.$body);
        $url = $this->config->baseUrl.'/'.ltrim($path, '/');

        try {
            $response = Http::timeout($this->config->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'X-MERCHANT-ID' => $this->config->merchantId,
                    'X-SIGNATURE' => $signature,
                    'X-TIMESTAMP' => $timestamp,
                ])
                ->withBody($body, 'application/json')
                ->send(strtoupper($method), $url);
        } catch (ConnectionException $e) {
            throw new ProviderNetworkException('nicepay', $e->getMessage(), $e);
        } catch (Throwable $e) {
            throw new ProviderApiException('nicepay', $e->getMessage(), 0, [], $e);
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new ProviderApiException(
                driver: 'nicepay',
                message: 'Invalid JSON response from Nicepay API.',
                httpStatus: $response->status(),
            );
        }

        if (! $response->successful()) {
            throw new ProviderApiException(
                driver: 'nicepay',
                message: (string) data_get($json, 'responseMessage', data_get($json, 'message', 'Nicepay API request failed.')),
                httpStatus: $response->status(),
                rawResponse: $json,
            );
        }

        return $json;
    }
}
