<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Interfaces\PaymentGatewayInterface;

class PaymentCallbackController extends Controller
{
    protected PaymentGatewayInterface $paymentGateway;

    public function __construct(PaymentGatewayInterface $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    public function processedCallback(Request $request)
    {
        return $this->paymentGateway->processedCallback($request);
    }

    public function responseCallback(Request $request): RedirectResponse
    {
        $response = $this->paymentGateway->responseCallback($request);
        if ($response) {

            return redirect()->route('payment.success');
        }

        return redirect()->route('payment.failed');
    }

    public function success()
    {
        return view('payment-success');
    }

    public function failed()
    {
        return view('payment-failed');
    }
}