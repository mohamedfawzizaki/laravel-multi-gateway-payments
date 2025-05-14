<?php

namespace App\Traits;

use App\Interfaces\PaymentGatewayTraitInterface;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Interfaces\PaymentGatewayInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Arr;

trait TabPaymentTrait
{
    public function ValidatePaymentPayload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',

            'customer' => 'required|array',
            'customer.first_name' => 'required|string|max:255',
            'customer.email' => 'required|email',

            'payment_method' => 'required|string|in:' . implode(
                ',',
                array_keys(config('payment.payment_gatways.tab.methods'))
            ),

            // 'reference' => 'required|array',
            // 'reference.transaction' => 'required|string',
            // 'reference.order' => 'required|string',

            // 'post' => 'required|array',
            // 'post.url' => 'required|url',

            // 'redirect' => 'required|array',
            // 'redirect.url' => 'required|url',
        ]);

        if ($validator->fails()) {
            Log::warning("Validation failed for Tap payment payload.", [
                'errors' => $validator->errors(),
            ]);

            // throw new HttpResponseException(
            //     response()->json(
            //         [
            //             'message' => 'Invalid data',
            //             'status' => false,
            //             'errors' => $validator->errors()
            //         ],
            //         422
            //     )
            // );
            return [
                'success' => false,
                'status' => 422,
                'message' => $validator->errors(),
                'data' =>  null
            ];
        }

        return  [
            'success' => true,
            'status' => 200,
            'data' =>  $validator->validated()
        ];
    }

    public function makePayload(array $requestData): array
    {
        $this->payload = $requestData;
        $this->payload['post']['url'] = $this->webhook_url;
        $this->payload['redirect']['url'] = $this->redirect_url;
        $this->payload['source']['id'] = config('payment.payment_gatways.' . $this->provider . '.methods.' . $requestData['payment_method'] . '.integration_id');

        return $this->payload;
    }

    public function makeResponse(array $paymentResposne)
    {
        if (!isset($paymentResposne['success']) || !$paymentResposne['success']) {
            return [
                'success' => $paymentResposne['success'],
                'status' => $paymentResposne['status'],
                'message' => 'Failed to initiate payment',
                'data' => $paymentResposne['data'] ?? null
            ];
        }

        return [
            'success' => $paymentResposne['success'],
            'status' => $paymentResposne['status'],
            'message' => 'Payment initiated successfully',
            'payment_url' => $paymentResposne['data']['transaction']['url'] ?? null,
            'transaction_id' => $paymentResposne['data']['id'] ?? null,
            'amount' => $paymentResposne['data']['amount'] ?? null,
            'currency' => $paymentResposne['data']['currency'] ?? null,
        ];
    }

    /**
     * Validate a Tap webhook using the 'hashstring' header.
     *
     * @param array  $payload       The webhook JSON data as an associative array.
     * @param string $receivedHash  The 'hashstring' header value from the webhook request.
     * @return bool  True if the computed HMAC matches the received hash, false otherwise.
     */
    public function validateHash(array $payload, string $receivedHash): bool
    {
        $secretKey = config('payment.payment_gatways.tab.secret_key');

        if (empty($secretKey)) {
            // Secret key is not configured
            return false;
        }

        // Determine webhook type. Tap typically includes an "object" field.
        $type = $payload['object'] ?? '';

        // Common fields
        $id       = $payload['id'] ?? '';
        $amount   = $payload['amount'] ?? 0;
        $currency = $payload['currency'] ?? '';
        $status   = $payload['status'] ?? '';

        // Format amount to correct decimals per ISO 4217
        $amount = $this->formatTapAmount($amount, $currency);

        // Determine references/fields based on type
        if (in_array(strtolower($type), ['invoice'])) {
            // Invoice webhook: uses 'updated' field
            $updated = $payload['updated'] ?? '';
            $created = $payload['created'] ?? '';

            // Build string: x_id...x_amount...x_currency...x_updated...x_status...x_created...
            $toBeHashedString = 'x_id' . $id
                . 'x_amount' . $amount
                . 'x_currency' . $currency
                . 'x_updated' . $updated
                . 'x_status' . $status
                . 'x_created' . $created;
        } else {
            // Charges/Authorize/Refund webhooks
            $gatewayRef = $payload['reference']['gateway'] ?? '';
            $paymentRef = $payload['reference']['payment'] ?? '';
            // Transaction created timestamp (may be nested for charges/authorize)
            $created = $payload['transaction']['created'] ?? $payload['created'] ?? '';

            // Build string: x_id...x_amount...x_currency...x_gateway_reference...x_payment_reference...x_status...x_created...
            $toBeHashedString = 'x_id' . $id
                . 'x_amount' . $amount
                . 'x_currency' . $currency
                . 'x_gateway_reference' . $gatewayRef
                . 'x_payment_reference' . $paymentRef
                . 'x_status' . $status
                . 'x_created' . $created;
        }

        // Compute HMAC SHA256 hash of the string using the secret key
        $calculatedHash = hash_hmac('sha256', $toBeHashedString, $secretKey);

        // Use timing-safe comparison to prevent timing attacks
        return hash_equals($calculatedHash, $receivedHash);
    }

    /**
     * Format the amount according to ISO 4217 decimals for the currency.
     * Ensures correct number of decimal places (e.g. 2.00 vs 3.000):contentReference[oaicite:13]{index=13}.
     *
     * @param float|int|string $amount
     * @param string $currency  Three-letter currency code (e.g. 'USD', 'BHD').
     * @return string  Formatted amount string.
     */
    protected function formatTapAmount($amount, string $currency): string
    {
        // Map of currency -> decimal places
        $decimalsMap = [
            'BHD' => 3,
            'KWD' => 3,
            'OMR' => 3,
            'JOD' => 3,
            'LYD' => 3,
            'TND' => 3,
            'JPY' => 0,
            'KRW' => 0,
            'VND' => 0,
            // Default to 2 if not specified
        ];

        $decimals = $decimalsMap[strtoupper($currency)] ?? 2;
        return number_format((float)$amount, $decimals, '.', '');
    }
}