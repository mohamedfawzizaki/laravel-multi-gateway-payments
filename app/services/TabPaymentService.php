<?php

namespace App\Services;

use App\Interfaces\PaymentGatewayInterface;
use App\Traits\TabPaymentTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TabPaymentService extends BasePaymentService implements PaymentGatewayInterface
{
    use TabPaymentTrait;
    protected array $payload = [];

    public function __construct()
    {
        parent::__construct();

        $this->header['Authorization'] = 'Bearer ' . $this->api_key;
    }

    public function sendPayment(Request $request) //: array
    {
        $validator = $this->ValidatePaymentPayload($request);

        if ($validator['success'] !== true) {
            return [
                'success' => $validator['success'],
                'status' => $validator['status'],
                'message' => $validator['message'],
                'data' => $validator['data'] ?? null
            ];
        }

        $this->payload = $this->makePayload($request->all());

        $paymentResposne = $this->buildRequest('POST', $this->charge_path, $this->payload)->getData(true);

        return $this->makeResponse($paymentResposne);
    }

    public function processedCallback(Request $request)
    {
        // Optional: Log entire payload for debugging
        Log::info('Tap webhook received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        $data = $request->all();

        // Optional: Verify 'hash' header if required
        $hashHeader = $request->header('hash');
        $hashString = $request->header('hashstring');

        if (!$this->validateHash($data, $hashString)) {
            // Invalid webhook signature
            Log::warning("Invalid signature");
            abort(400, 'Invalid signature');
        }

        // Validate charge object
        if (isset($data['object']) && $data['object'] === 'charge') {
            $status = $data['status'] ?? null;
            $transactionId = $data['id'] ?? null;
            $amount = $data['amount'] ?? null;
            $currency = $data['currency'] ?? null;
            $reference = $data['reference']['transaction'] ?? null;

            if ($status === 'CAPTURED') {
                // ✅ Successful payment
                // You might update an order or payment record in your database
                // Example: Order::where('transaction_id', $reference)->update([...]);

                Log::info("Payment captured: {$transactionId}");
            } elseif (in_array($status, ['FAILED', 'DECLINED'])) {
                // ❌ Failed payment
                Log::warning("Payment failed: {$transactionId}");
                // Update records accordingly
            } else {
                // Other statuses like INITIATED, ABANDONED, etc.
                Log::info("Unhandled status '{$status}' for transaction {$transactionId}");
            }
        }

        // Always respond 200 to acknowledge receipt, otherwise Tap may retry
        return response()->json(['message' => 'Webhook received'], 200);
    }

    public function responseCallback(Request $request): bool
    {
        $response = $request->all();
        Storage::put('tab_response.json', json_encode($request->all()));

        if (isset($response['success']) && $response['success'] === 'true') {

            return true;
        }
        return false;
    }
}