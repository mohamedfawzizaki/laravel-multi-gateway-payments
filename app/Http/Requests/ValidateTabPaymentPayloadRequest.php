<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ValidateTabPaymentPayloadRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',

            'customer' => 'required|array',
            'customer.first_name' => 'required|string|max:255',
            'customer.email' => 'required|email',

            'payment_method' => 'required|string|in:' . implode(
                ',',
                array_keys(config('payment.payment_gatways.tab.methods'))
            ),

            // 'reference' => 'required|array',
            // 'reference.transaction' => 'required|string',
            // 'reference.order' => 'required|string',

            // 'post' => 'required|array',
            // 'post.url' => 'required|url',

            // 'redirect' => 'required|array',
            // 'redirect.url' => 'required|url',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        Log::warning("Validation failed for Tap payment payload.", [
            'errors' => $validator->errors(),
        ]);

        throw new HttpResponseException(
            response()->json(
                [
                    'message' => 'Invalid data',
                    'status' => false,
                    'errors' => $validator->errors()
                ],
                422
            )
        );
    }
}