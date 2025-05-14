<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ValidatePaymobPaymentPayloadRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'payment_method' => 'required|string|in:' . implode(
                ',',
                array_keys(config('payment.payment_gatways.paymob.methods'))
            ),
            // 'amount_cents' => 'required|numeric',
            // 'currency' => 'required|string',
            // 'items' => 'required|array',
            'order_id'=>'required|string',
            'billing_data' => 'required|array',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        Log::warning("Validation failed for paymob data.", [
            'errors' => $validator->errors(),
        ]);

        throw new HttpResponseException(
            response()->json(
                [
                    'message' => 'Invalid data',
                    'status' => false,
                    'errors' => $validator->errors()
                ],
                403
            )
        );
    }
}