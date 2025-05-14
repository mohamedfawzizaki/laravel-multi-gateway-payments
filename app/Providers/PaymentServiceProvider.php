<?php

namespace App\Providers;

use Illuminate\Http\Request;
use App\Services\TabPaymentService;
use App\Services\PaymobPaymentService;
use Illuminate\Support\ServiceProvider;
use App\Interfaces\PaymentGatewayInterface;
use App\Http\Requests\ValidateTabPaymentPayloadRequest;
use App\Http\Requests\ValidatePaymobPaymentPayloadRequest;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // $this->app->bind(PaymentGatewayInterface::class, PaymobPaymentService::class);
        // $this->app->bind(PaymentGatewayInterface::class, TabPaymentService::class);

        $this->app->bind(PaymentGatewayInterface::class, function ($app) {

            $gateway = config('payment.current_gateway'); // e.g., "tab" or "paymob"

            return match ($gateway) {
                'tab' => new TabPaymentService(),
                'paymob' => new PaymobPaymentService(),
                default => throw new \Exception("Unsupported payment gateway: $gateway"),
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}