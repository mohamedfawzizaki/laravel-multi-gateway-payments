<?php

namespace App\Traits;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

trait MyFatoorahPaymentTrait
{
    public function ValidatePaymentPayload(array $payload)
    {
        $validator = Validator::make(
            $payload,
            [
                'InvoiceValue' => ['required', 'numeric', 'min:0.01'],
                'CustomerName' => ['required', 'string', 'max:255'],
                'NotificationOption' => ['required', Rule::in(['EML', 'SMS', 'LNK', 'ALL'])],
                'CustomerEmail' => [
                    'required_if:NotificationOption,EML,ALL',
                    'email',
                ],
                'CustomerMobile' => [
                    'required_if:NotificationOption,SMS,ALL',
                    'string',
                    'max:20',
                ],

                'payment_method' => 'sometimes|string|in:' . implode(
                    ',',
                    array_keys(config('payment.payment_gatways.myfatoorah.methods'))
                ),
                'WebhookUrl' => ['nullable', 'url'], // 👈 Optional, but must be a valid URL if present
                'CallBackUrl' => ['sometimes', 'url'], // 👈 Optional, but must be a valid URL if present
                'ErrorUrl' => ['nullable', 'url'], // 👈 Optional, but must be a valid URL if present
            ],
            [
                'InvoiceValue.required' => 'The invoice value is required.',
                'InvoiceValue.numeric' => 'The invoice value must be a number.',
                'InvoiceValue.min' => 'The invoice value must be at least 0.01.',
                'CustomerName.required' => 'The customer name is required.',
                'NotificationOption.required' => 'The notification option is required.',
                'NotificationOption.in' => 'The notification option must be one of: EML, SMS, LNK, ALL.',
                'CustomerEmail.required_if' => 'Customer email is required for EML or ALL notification options.',
                'CustomerEmail.email' => 'Customer email must be a valid email address.',
                'CustomerMobile.required_if' => 'Customer mobile is required for SMS or ALL notification options.',
            ]
        );

        if ($validator->fails()) {
            Log::warning("Validation failed for MyFatoorah payment payload.", [
                'errors' => $validator->errors(),
            ]);

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

        
        if (!empty($this->webhook_url)) {
            $this->payload['WebhookUrl']  = $this->webhook_url;
        }
        if (!empty($this->callback_url)) {
            $this->payload['CallBackUrl']  = $this->callback_url;
        }
        if (!empty($this->error_callback_url)) {
            $this->payload['ErrorUrl']  = $this->error_callback_url;
        }
        
        $validator = $this->ValidatePaymentPayload($this->payload);
        
        if ($validator['success'] !== true) {
            return [
                'success' => $validator['success'],
                'status' => $validator['status'],
                'message' => $validator['message'],
                'data' => $validator['data']
            ];
        }

        return [
            'success' => $validator['success'],
            'status' => $validator['status'],
            'data' => $validator['data']
        ];
    }

    public function makeResponse(array $paymentResposne)
    {
        if (!isset($paymentResposne['success']) || !$paymentResposne['success']) {
            return [
                'success' => $paymentResposne['success'],
                'status' => $paymentResposne['status'],
                'message' => 'Failed to initiate payment',
                'data' => null
            ];
        }

        return [
            'success' => $paymentResposne['success'],
            'status' => $paymentResposne['status'],
            'message' => 'Payment initiated successfully',
            'payment_url' => $paymentResposne['data']['Data']['InvoiceURL'] ?? null,
            'transaction_id' => $paymentResposne['data']['id'] ?? null,
            'amount' => $this->payload['InvoiceValue'] ?? null,
            'currency' => '',
        ];
    }
}