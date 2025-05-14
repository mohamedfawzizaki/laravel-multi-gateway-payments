<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ValidateLoginCredintialRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email'     => ['required', 'email', 'exists:users',],
            'password'  => ['required', 'min:8'],
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        Log::warning("Validation failed for User login.", [
            'errors' => $validator->errors(),
        ]);

        throw new HttpResponseException(
            response()->json(
                [
                    'message' => 'Invalid user credintials',
                    'status' => false,
                    'errors' => $validator->errors()
                ],
                403
            )
        );
    }
}