<?php

namespace App\Exceptions\Payments;

use Exception;

class InvlidPaymentMethodException extends Exception
{
    // public function __construct($message = "Something went wrong", $code = 0, Exception $previous = null)
    // {
    //     parent::__construct($message, $code, $previous);
    // }

    // public function report()
    // {
    //     // Optional: Log or report the exception
    //     \Log::error("CustomException: " . $this->getMessage());
    // }

    // public function render($request)
    // {
    //     // Optional: Customize HTTP response
    //     return response()->json([
    //         'error' => 'Custom Exception',
    //         'message' => $this->getMessage()
    //     ], 400);
    // }
}