<?php

namespace App\Services;

use App\Interfaces\PaymentGatewayInterface;
use App\Traits\MyFatoorahPaymentTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MyFatoorahPayment extends BasePaymentService implements PaymentGatewayInterface
{
    use MyFatoorahPaymentTrait;
    protected array $payload = [];

    protected string $error_callback_url;

    public function __construct()
    {
        parent::__construct();

        $this->header['Authorization'] = 'Bearer ' . $this->api_key;

        $this->error_callback_url = config('payment.payment_gatways.' . $this->provider . '.error_callback', '');
    }

    public function sendPayment(Request $request) //: array
    {
        $this->payload = $this->makePayload($request->all());

        
        $paymentResposne = $this->buildRequest('POST', $this->charge_path, $this->payload['data'])->getData(true);
        
        if (!$paymentResposne['success']) {
            Log::error('MyFatoorahPayment sendPayment error: ' . $paymentResposne['data']['Message']);
            return $this->makeUnifiedResponse(
                $paymentResposne['success'],
                $paymentResposne['status'],
            );
        }

        return $this->makeUnifiedResponse(
            $paymentResposne['success'],
            $paymentResposne['status'],
            $paymentResposne['data']['Data']['InvoiceURL'],
            '',
            $this->payload['data']['InvoiceValue'],
            '',
        );
    }

    public function webhook(Request $request)
    {
        // Optional: Log entire payload for debugging
        Log::info('Tap webhook received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        $data = $request->all();

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

    public function callback(Request $request): bool
    {
        $response = $request->all();
        Storage::put('tab_response.json', json_encode($request->all()));

        if (isset($response['success']) && $response['success'] === 'true') {

            return true;
        }
        return false;
    }
}