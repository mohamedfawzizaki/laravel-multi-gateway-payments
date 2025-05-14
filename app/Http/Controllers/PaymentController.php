<?php

namespace App\Http\Controllers;

use App\Interfaces\PaymentGatewayInterface;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected PaymentGatewayInterface $paymentGateway;

    public function __construct(PaymentGatewayInterface $paymentGateway)
    {

        $this->paymentGateway = $paymentGateway;
    }

    public function paymentProcess(Request $request)
    {
        try {
            $response = $this->paymentGateway->sendPayment($request);

            if (!isset($response['success']) || !$response['success']) {
                return response()->json([
                    'success' => $response['success'],
                    'status' => $response['status'],
                    'message' => $response['message'],
                    'data' => $response['data'] ?? null
                ], 400);
            }

            return response()->json([
                'success' => $response['success'],
                'status' => $response['status'],
                'message' => 'Payment initiated successfully',
                'payment_url' => $response['payment_url'] ?? null,
                'transaction_id' => $response['transaction_id'] ?? null,
                'amount' => $response['amount'] ?? null,
                'currency' => $response['currency'] ?? null,
            ], 200);
        } catch (Exception $e) {
            Log::error('Failed to initiate payment: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'An error occurred while initiating the payment',
            ], 500);
        }
    }
}