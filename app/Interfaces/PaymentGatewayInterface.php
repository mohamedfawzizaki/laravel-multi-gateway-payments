<?php

namespace App\Interfaces;

use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function sendPayment(Request $request);
    public function processedCallback(Request $request);
    public function responseCallback(Request $request);
}