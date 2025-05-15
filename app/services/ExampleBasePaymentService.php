<?php

namespace App\Services;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use App\Exceptions\Payments\BillingDataException;
use App\Exceptions\Payments\InvlidPaymentMethodException;
use App\Exceptions\Payments\InvlidPaymentBillingDataException;
use App\Exceptions\Payments\FailedToGeneratePaymentKeyException;

class ExampleBasePaymentService
{
    protected string $base_url;
    protected string $auth_path;
    protected string $order_path;
    protected string $payment_key_path;
    protected string $iframe_path;
    protected string $unified_intention_path;
    protected string $unified_checkout_path;
    protected string $api_key;
    protected string $secret_key;
    protected string $public_key;
    protected array $paymentMethods;
    protected array $header;
    protected string $base_currency;
    protected array $billingDataColumns;
    
    
    
    protected string $webhook_url;
    protected string $callback_url;
    
    public function __construct(string $provider)
    {
        $this->base_url    = rtrim(config('payment.payment_gatways.' . $provider . '.base_url'), '/');
        $this->auth_path   = '/' . trim(config('payment.payment_gatways.' . $provider . '.auth_path'));
        $this->order_path  = '/' . trim(config('payment.payment_gatways.' . $provider . '.order_path'));
        $this->payment_key_path  = '/' . trim(config('payment.payment_gatways.' . $provider . '.payment_key_path'));
        $this->iframe_path = '/' . trim(config('payment.payment_gatways.' . $provider . '.iframe_path'));
        $this->api_key     = config('payment.payment_gatways.' . $provider . '.api_key');
        $this->public_key     = config('payment.payment_gatways.' . $provider . '.public_key');
        $this->secret_key     = config('payment.payment_gatways.' . $provider . '.secret_key');
        $this->header      = config('payment.payment_gatways.' . $provider . '.header');

        $this->paymentMethods = config('payment.payment_gatways.' . $provider . '.methods');
        $this->base_currency  = config('payment.payment_gatways.' . $provider . '.base_currency');
        $this->billingDataColumns = config('payment.payment_gatways.' . $provider . '.required_billing_data_columns', []);


        $this->unified_intention_path = '/' . trim(config('payment.payment_gatways.' . $provider . '.unified_intention_path'));
        $this->unified_checkout_path = '/' . trim(config('payment.payment_gatways.' . $provider . '.unified_checkout_path'));
    
    
        $this->webhook_url  = env('WEBHOOK_URL', '');
        $this->callback_url = env('callback_url', '');
    
    
    }

    protected function buildRequest($method, $url, $data = null, $type = 'json'): JsonResponse
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

    protected function generateToken() // '/api/auth/tokens'
    {
        $response = $this->buildRequest(
            'POST',
            '/' . trim($this->auth_path, '/'),
            ['api_key' => $this->api_key]
        );

        $token = $response->getData(true)['data']['token'];

        $this->header['Authorization'] = 'Bearer ' . $token;

        return $token;
    }

    protected function validatePaymentMethod(string $method): string
    {
        return array_key_exists($method, $this->paymentMethods) ? $method :
            throw new InvlidPaymentMethodException('invalid payment methods, valid methods are : ' . implode(', ', array_keys($this->paymentMethods)));
    }

    protected function validateBillingData(array $billingData, string $paymentMethod): array
    {
        $missedColumns = [];
        foreach ($this->billingDataColumns as $column) {
            if (!array_key_exists($column, $billingData) && !in_array($column, ['phone_number', 'national_id', 'first_name'])) {
                $missedColumns[] = $column;
            }
        }

        if (!empty($missedColumns)) {
            throw new BillingDataException('missing billing data columns : ' . implode(', ', $missedColumns));
        }

        switch ($paymentMethod) {
            case 'wallet':
                if (empty($billingData['phone_number'])) {
                    throw new BillingDataException('missing billing data columns, Phone number required for wallet payments.');
                }
                break;
            case 'valu':
                if (empty($billingData['national_id'])) {
                    throw new BillingDataException('missing billing data columns, National ID required for valu payments.');
                }

                if (empty($billingData['first_name'])) {
                    throw new BillingDataException('missing billing data columns, First name is required for valu validation.');
                }
                break;
        }

        return $billingData;
    }

    protected function checkIfOrderAlreadyCreated(string $orderId): JsonResponse
    {
        $paymentGatewayOrder = $this->buildRequest('GET', $this->order_path . '?merchant_order_id=' . $orderId);

        return $paymentGatewayOrder;
    }

    protected function createPaymentGatewayOrder(string $token, string $orderId, $shipping_data = []): JsonResponse
    {
        // featch form the order table or form the orderService:
        // $order = Order::find($orderId);
        $order = (object) [
            "id" => $orderId,
            "user_id" => "0195f749-d4a0-70a7-94b5-26a3f4f73984",
            "order_number" => "ORD-20250508-CGJMFM",
            "subtotal" => "22.00",
            "tax" => "0.00",
            "total_price" => "22.00",
            "currency_code" => "USD",
            "status" => "pending",
            "created_at" => "2025-05-08T10:42:23.000000Z",
            "updated_at" => "2025-05-08T10:42:23.000000Z",
            "deleted_at" => null,
            "order_items" => [
                [
                    "id" => 1,
                    "name" => 't-shirt',
                    "order_id" => 1,
                    "vendor_order_id" => 1,
                    "product_id" => 1,
                    "product" => [
                        "id" => 1,
                        "name" => "t-shirt",
                        "description" => "this is s t-shert"
                    ],
                    "variation_id" => 1,
                    "quantity" => 4,
                    "price" => "5.50",
                    "subtotal" => "22.00",
                    "status" => "pending",
                    "is_digital" => 0,
                    "download_url" => null,
                    "download_expiry" => null,
                    "is_returnable" => 1,
                    "return_by_date" => null,
                    "created_at" => "2025-05-08T10:42:23.000000Z",
                    "updated_at" => "2025-05-08T10:42:23.000000Z",
                ],
            ]
        ];

        $orderItems = array_map(function ($item) use ($order) {
            return [
                'name' => $item['product']['name'],
                'amount_cents' => (int) $this->convertToPaymentGatewayCurrency($order->currency_code, $item['subtotal']),
                'description' => $item['product']['description'],
                'quantity' => $item['quantity'],
            ];
        }, $order->order_items);

        $amountCents = $this->convertToPaymentGatewayCurrency($order->currency_code, $order->total_price);

        $existingPaymentGatewayOrder = $this->buildRequest('GET', $this->order_path . '?merchant_order_id=' . $orderId);

        if (!empty($existingPaymentGatewayOrder->getData(true)['data']['results'])) {
            # update order tabel: external_order_id = orderPaymentID
            $order->external_order_id = $existingPaymentGatewayOrder->getData(true)['data']['results']['id'];
            $order->save();
            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $existingPaymentGatewayOrder->getData(true)['data']['results'][0],
            ], 200);
        }
        # make this dynamic to corelate with other providers payload
        $paymentGatewayOrder = $this->buildRequest('POST', $this->order_path, [
            'auth_token' => $token,
            'delivery_needed' => false,
            'amount_cents' => $amountCents,
            'currency' => $this->base_currency,
            'merchant_order_id' => $orderId,
            'items' => $orderItems
        ]);

        # update order tabel: external_order_id = orderPaymentID
        $order->external_order_id = $paymentGatewayOrder->getData(true)['data']['results']['id'];
        $order->save();

        return $paymentGatewayOrder;
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

    protected function generatePaymentKey(array $paymentPayload): string
    {
        $response = $this->buildRequest('POST', $this->payment_key_path, $paymentPayload);

        if ($response->getData(true)['success']) {
            return $response->getData(true)['data']['token'];
        }

        throw new FailedToGeneratePaymentKeyException('Failed to generate payment key.');
    }

    protected function generateIFrameUrl(string $iframeId, string $paymentToken)
    {
        return $this->base_url . '/' . $this->iframe_path . '/' . $iframeId . '?payment_token=' . $paymentToken;
        // $url = "https://accept.paymob.com/api/acceptance/iframes/$iframeId?payment_token=$paymentToken";
    }

















    protected function getOrderItems(string $orderId)
    {
        // featch form the order table or form the orderService:
        // $order = Order::find($orderId);
        $order = (object) [
            "id" => $orderId,
            "user_id" => "0195f749-d4a0-70a7-94b5-26a3f4f73984",
            "order_number" => "ORD-20250508-CGJMFM",
            "subtotal" => "88",
            "tax" => "0.00",
            "total_price" => "88",
            "currency_code" => "USD",
            "status" => "pending",
            "created_at" => "2025-05-08T10:42:23.000000Z",
            "updated_at" => "2025-05-08T10:42:23.000000Z",
            "deleted_at" => null,
            "order_items" => [
                [
                    "id" => 1,
                    "name" => 't-shirt',
                    "order_id" => 1,
                    "vendor_order_id" => 1,
                    "product_id" => 1,
                    "product" => [
                        "id" => 1,
                        "name" => "t-shirt",
                        "description" => "this is s t-shert"
                    ],
                    "variation_id" => 1,
                    "quantity" => 4,
                    "price" => "5.50",
                    "subtotal" => "22.00",
                    "status" => "pending",
                    "is_digital" => 0,
                    "download_url" => null,
                    "download_expiry" => null,
                    "is_returnable" => 1,
                    "return_by_date" => null,
                    "created_at" => "2025-05-08T10:42:23.000000Z",
                    "updated_at" => "2025-05-08T10:42:23.000000Z",
                ],
            ]
        ];

        $orderItems = array_map(function ($item) use ($order) {
            return [
                'name' => $item['product']['name'],
                'amount' => (int) $this->convertToPaymentGatewayCurrency($order->currency_code, $item['subtotal']),
                'description' => $item['product']['description'],
                'quantity' => $item['quantity'],
            ];
        }, $order->order_items);

        return [
            'order_items' => $orderItems,
            'amount_cents' => $this->convertToPaymentGatewayCurrency($order->currency_code, $order->total_price),
        ];
    }
    protected function generateUnifiedCheckOutUrl(string $clientSecret)
    {
        return $this->base_url . $this->unified_checkout_path . '/' . '?publicKey=' . $this->public_key . '&clientSecret=' . $clientSecret;
        // $url = "https://accept.paymob.com/unifiedcheckout/?publicKey=egy_pk_test_vU9dkgUJZZpNp5w043fpR2GCSMT4NPmc&clientSecret=egy_csk_test_8f7c5132f69df74e2d590c4fd673e0c2";
    }
}