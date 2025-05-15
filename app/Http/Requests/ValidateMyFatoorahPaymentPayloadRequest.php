<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ValidateMyFatoorahPaymentPayloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
        ];
    }

    public function messages(): array
    {
        return [
            'InvoiceValue.required' => 'The invoice value is required.',
            'InvoiceValue.numeric' => 'The invoice value must be a number.',
            'InvoiceValue.min' => 'The invoice value must be at least 0.01.',
            'CustomerName.required' => 'The customer name is required.',
            'NotificationOption.required' => 'The notification option is required.',
            'NotificationOption.in' => 'The notification option must be one of: EML, SMS, LNK, ALL.',
            'CustomerEmail.required_if' => 'Customer email is required for EML or ALL notification options.',
            'CustomerEmail.email' => 'Customer email must be a valid email address.',
            'CustomerMobile.required_if' => 'Customer mobile is required for SMS or ALL notification options.',
        ];
    }
}