<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Interfaces\PaymentGatewayInterface;

class PaymobPaymentService extends ExampleBasePaymentService
{
    protected $provider = 'paymob';

    public function __construct()
    {
        parent::__construct($this->provider);
    }

    public function sendPayment(Request $request) //: array
    {
        $token = $this->generateToken();

        $data  = $request->validated();

        $data['api_source'] = "INVOICE";

        $paymentMethod = $this->validatePaymentMethod($data['payment_method']);

        $billingData   = $this->validateBillingData($data['billing_data'], $paymentMethod);

        $integrationId = $this->paymentMethods[$paymentMethod]['integration_id'];
        $iframeId      = $this->paymentMethods[$paymentMethod]['iframe_id'];

        // Create the order on Paymob
        $paymentGatewayOrder = $this->createPaymentGatewayOrder($token, $data['order_id']);

        if (!$paymentGatewayOrder->getData(true)['success']) {
            return ['success' => false, 'url' => route('payment.failed')];
        }

        $paymentGatewayOrderId = $paymentGatewayOrder->getData(true)['data']['id'];

        $amountInCents = $paymentGatewayOrder->getData(true)['data']['amount_cents'];

        $paymentPayload = $this->createPaymentPayload(
            $billingData,
            $data,
            $amountInCents,
            $token,
            $paymentGatewayOrderId,
            $integrationId
        );

        // Get payment key token
        $paymentToken = $this->generatePaymentKey($paymentPayload);

        // Build redirect iframe URL
        $url = $this->generateIFrameUrl($iframeId, $paymentToken);

        return $url;
    }

    public function sendUnifiedPayment(Request $request) //: array
    {
        $this->header['Authorization'] = 'Bearer ' . $this->secret_key;

        $data  = $request->validated();

        $data['api_source'] = "INVOICE";

        $paymentMethod = $this->validatePaymentMethod($data['payment_method']);

        $billingData   = $this->validateBillingData($data['billing_data'], $paymentMethod);

        $integrationId = $this->paymentMethods[$paymentMethod]['integration_id'];
        $iframeId      = $this->paymentMethods[$paymentMethod]['iframe_id'];

        $orderItemsAndAmountInCents = $this->getOrderItems($data['order_id']);
        $orderItems = $orderItemsAndAmountInCents['order_items'];
        $amountInCents = $orderItemsAndAmountInCents['amount_cents'];


        $paymentPayload = $this->createUnifiedPaymentPayload(
            $amountInCents,
            $paymentMethod,
            $billingData,
            $orderItems,
            [],
            [],
            'https://localhost:443/api/payment/processed-callback',
            'https://localhost:443/api/payment/response-callback'
        );

        $paymentIntention = $this->buildRequest('POST', $this->unified_intention_path, data: $paymentPayload);
        // var_dump($paymentIntention);die;

        if (!$paymentIntention->getData(true)['success']) {
            return ['success' => false, 'url' => route('payment.failed')];
        }

        $clientSecret = $paymentIntention->getData(true)['data']['client_secret'];
        // Build redirect UnifiedCheckOutUrl
        $url = $this->generateUnifiedCheckOutUrl($clientSecret);

        return $url;
    }

    private function createPaymentPayload(
        $billingData,
        $data,
        $amountInCents,
        $token,
        $paymentGatewayOrderId,
        $integrationId
    ): array {
        return [
            'auth_token' => $token,
            'amount_cents' => $data['amount_cents'] ?? $amountInCents,
            'expiration' => 3600,
            'order_id' => $paymentGatewayOrderId,
            'billing_data' => $billingData,
            'currency' => $data['currency'] ?? $this->base_currency,
            'integration_id' => $integrationId,
        ];
    }

    private function createUnifiedPaymentPayload(
        $amountInCents,
        $payment_methods,
        $billingData,
        $orderItems,
        $customer,
        $extras,
        $notification_url,
        $redirection_url
    ): array {
        $customer = [
            'first_name' => 'mohamed',
            'last_name' => 'fawzi',
            'email' => 'mo@gmail.com'
        ];
        return [
            'amount' => $amountInCents,
            'currency' => $this->base_currency,
            'payment_methods' => [$payment_methods],
            'items' => $orderItems,
            'billing_data' => $billingData,
            'customer' => $customer,
            // 'extras' => $extras,
            'special_reference' => 'phe4sjw11q-' . Str::random(16), // or use uniqid() or a custom generator
            'notification_url' => $notification_url,
            'redirection_url' => $redirection_url,
            'expiration' => 3600,
        ];

        //         "amount": 10,
        //   "currency": "EGP",
        //   "payment_methods": [
        //     5084863,
        //     "card",
        //     "5084863"
        //   ],
        //   "items": [
        //     {
        //       "name": "Item name 1",
        //       "amount": 10,
        //       "description": "Watch",
        //       "quantity": 1
        //     }
        //   ],
        //   "billing_data": {
        //     "apartment": "6",
        //     "first_name": "Ammar",
        //     "last_name": "Sadek",
        //     "street": "938, Al-Jadeed Bldg",
        //     "building": "939",
        //     "phone_number": "+96824480228",
        //     "country": "OMN",
        //     "email": "AmmarSadek@gmail.com",
        //     "floor": "1",
        //     "state": "Alkhuwair"
        //   },
        //   "customer": {
        //     "first_name": "Ammar",
        //     "last_name": "Sadek",
        //     "email": "AmmarSadek@gmail.com",
        //     "extras": {
        //       "re": "22"
        //     }
        //   },
        //   "extras": {
        //     "ee": 22
        //   },
        //   "special_reference": "phe4sjw11q-1xxxxxxxxx3",
        //   "expiration": 3600,
        //   "notification_url": "https://localhost:443/api/payment/processed-callback",
        //   "redirection_url": "https://localhost:443/api/payment/response-callback"
        // }

    }

    public function webhook(Request $request)
    {
        // Log the full webhook payload
        Log::info('Payment Callback Received:', $request->all());

        $data = $request->input('obj');

        // Validate essential fields
        if (!$data || !isset($data['id'], $data['order']['id'], $data['amount_cents'])) {
            return response()->json(['error' => 'Invalid webhook payload'], 400);
        }

        // Extract data
        $transactionId = $data['id'];
        $orderPaymentId = $data['order']['id'];
        $amount = $data['amount_cents'];
        $success = $data['success'] ?? false;

        // Find your internal order (example using 'order_reference')
        // $order = \App\Models\Order::where('external_order_id', $orderId)->first();
        $order = (object) [];

        if (!$order) {
            Log::warning("Order not found for callback: Order ID {$orderPaymentId}");
            return response()->json(['error' => 'Order not found'], 404);
        }

        if ($success) {
            // Update order/payment status
            $order->payment_status = 'paid';
            $order->payment_reference = $transactionId;
            $order->paid_at = now();
            $order->save();

            Log::info("Order {$order->id} marked as paid via callback.");
        } else {
            Log::warning("Payment failed for Order ID {$orderPaymentId}");
        }

        return response()->json(['message' => 'Callback processed']);
    }

    public function callback(Request $request): bool
    {
        $response = $request->all();
        Storage::put('paymob_response.json', json_encode($request->all()));

        if (isset($response['success']) && $response['success'] === 'true') {

            return true;
        }
        return false;
    }
}