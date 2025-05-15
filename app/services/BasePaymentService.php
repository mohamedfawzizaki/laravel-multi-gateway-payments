<?php

namespace App\Services;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use App\Exceptions\Payments\BillingDataException;
use App\Exceptions\Payments\InvlidPaymentMethodException;
use App\Exceptions\Payments\InvlidPaymentBillingDataException;
use App\Exceptions\Payments\FailedToGeneratePaymentKeyException;
use Illuminate\Validation\Rule;

class BasePaymentService
{
    protected string $provider;
    protected string $base_url;
    protected string $charge_path;
    protected ?string $auth_path;
    protected string $api_key;
    protected ?string $secret_key;
    protected ?string $public_key;
    protected ?array $paymentMethods;
    protected array $header;
    protected ?string $base_currency;
    protected ?string $webhook_url;
    protected ?string $callback_url;

    public function __construct()
    {
        $this->provider    = config('payment.current_gateway');

        $this->base_url    = rtrim(config('payment.payment_gatways.' . $this->provider . '.base_url', ''), '/');
        $this->charge_path   = '/' . trim(config('payment.payment_gatways.' . $this->provider . '.charge_path', ''), '/');
        $this->auth_path   = '/' . trim(config('payment.payment_gatways.' . $this->provider . '.auth_path', ''), '/');
        $this->api_key     = config('payment.payment_gatways.' . $this->provider . '.api_key');
        $this->public_key  = config('payment.payment_gatways.' . $this->provider . '.public_key', '');
        $this->secret_key  = config('payment.payment_gatways.' . $this->provider . '.secret_key', '');
        $this->header      = config('payment.payment_gatways.' . $this->provider . '.header', []);

        $this->paymentMethods = config('payment.payment_gatways.' . $this->provider . '.methods', []);
        $this->base_currency  = config('payment.payment_gatways.' . $this->provider . '.base_currency', '');

        $this->webhook_url  = config('payment.payment_gatways.' . $this->provider . '.webhook', '');
        $this->callback_url  = config('payment.payment_gatways.' . $this->provider . '.callback', '');
    }

    protected function buildRequest($method, $url, $data = null, $type = 'json')
    {
        try {
            //type ? json || form_params
            $response = Http::withHeaders($this->header)->send($method, $this->base_url . $url, [
                $type => $data
            ]);
            return response()->json([
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
            ], $response->status());
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    protected function makeUnifiedResponse(bool $success, int $status, string $paymentUrl = '', string $transactionId = '', ?float $amount = null, string $currency = '')
    {
        if (!isset($success) || !$success) {
            return [
                'success' => $success,
                'status' => $status,
                'message' => 'Failed to initiate payment',
                'data' => null
            ];
        }

        return [
            'success' => $success,
            'status' => $status,
            'message' => 'Payment initiated successfully',
            'payment_url' => $paymentUrl,
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'currency' => $currency,
        ];
    }












    protected function convertToPaymentGatewayCurrency($from, $amount)
    {
        // 1. Get exchange rate between $from and $to
        $rate = $this->getExchangeRate($from, $this->base_currency); // Implement this function or use a fixed rate

        // 2. Convert amount to target currency
        $converted = $amount * $rate;

        // 3. Convert to smallest unit (e.g., cents)
        $multiplier = $this->getCurrencyMultiplier($this->base_currency);

        return (int) round($converted * $multiplier);
    }

    # You could use a fixed config or real-time API:
    # For real rates, integrate with something like Fixer.io, Open Exchange Rates, or your internal Forex API.
    protected function getExchangeRate($from, $to)
    {
        $rates = [
            'USD_EGP' => 30.5,
            'EUR_EGP' => 33.2,
            'EGP_EGP' => 1,
        ];

        return $rates["{$from}_{$to}"] ?? 1;
    }

    protected function getCurrencyMultiplier($currency)
    {
        $map = [
            'USD' => 100,
            'EUR' => 100,
            'EGP' => 100,
            'JPY' => 1,
            'KWD' => 1000,
            'BHD' => 1000,
            'OMR' => 1000,
        ];

        return $map[$currency] ?? 100; // Default to 100 if unknown
    }
}