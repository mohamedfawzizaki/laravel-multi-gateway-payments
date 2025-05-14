<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'      => ['required', 'string', 'min:3', 'max:50',],
            'email'     => ['required', 'email', 'unique:users',],
            'password'  => ['required', 'min:8', 'confirmed',],
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        Log::warning("Validation failed for record creation.", [
            'errors' => $validator->errors(),
        ]);

        throw new HttpResponseException(
            response()->json(
                [
                    'message' => 'Validation errors',
                    'status' => false,
                    'errors' => $validator->errors()
                ],
                422
            )
        );
    }
}