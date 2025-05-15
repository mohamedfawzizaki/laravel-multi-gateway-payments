<?php

namespace App\Interfaces;

use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function sendPayment(Request $request);
    public function webhook(Request $request);
    public function callback(Request $request);
}